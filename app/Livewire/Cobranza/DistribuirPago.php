<?php

namespace App\Livewire\Cobranza;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** Destino del pago ≠ fecha del ingreso. No genera ingresos nuevos. */
class DistribuirPago extends Component
{
    public int $pagoId;
    public string $periodo = '';
    public string $monto = '';

    public function mount(int $pagoId): void
    {
        $pago = DB::table('isp_pagos')->where('id', $pagoId)->first();
        abort_unless($pago && $pago->categoria === 'internet', 404);
        $this->pagoId = $pagoId;
        $this->periodo = CarbonImmutable::parse($pago->fecha_pago)->format('Y-m');
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'periodo' => ['required', 'date_format:Y-m'],
            'monto' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
        ]);
        $periodo = $datos['periodo'].'-01';
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-01$/D', $periodo) ||
            $periodo > today('America/Lima')->startOfMonth()->toDateString()) {
            throw ValidationException::withMessages(['periodo' => 'Selecciona un mes válido, no futuro.']);
        }
        DB::transaction(function () use ($datos, $periodo) {
            $pago = DB::table('isp_pagos')->where('id', $this->pagoId)->first();
            if (!$pago || $pago->categoria !== 'internet') abort(404);
            // Coordinar escrituras para el mismo cliente con generador mensual y otros cobros.
            DB::table('clientes')->where('id', $pago->cliente_id)->lockForUpdate()->first();
            $pago = DB::table('isp_pagos')->where('id', $this->pagoId)->lockForUpdate()->first();
            $centavos = $this->centavos($datos['monto']);
            $aplicadoCargo = $this->centavos(DB::table('isp_aplicaciones_pago')->where('pago_id', $pago->id)->sum('monto'));
            $aplicadoMes = $this->centavos(DB::table('isp_repartos_periodos')->where('pago_id', $pago->id)->sum('monto'));
            $reservado = $this->centavos(DB::table('isp_anticipos_internet')->where('pago_id', $pago->id)
                ->selectRaw('COALESCE(SUM(monto - monto_aplicado), 0) as total')->value('total'));
            $disponible = $this->centavos($pago->monto) - $aplicadoCargo - $aplicadoMes - $reservado;
            if ($centavos > $disponible) {
                throw ValidationException::withMessages(['monto' => 'Solo quedan S/ '.number_format(max(0,$disponible)/100,2).' de este pago sin asignar.']);
            }
            if (DB::table('isp_repartos_periodos')->where('pago_id', $pago->id)->where('periodo', $periodo)->exists()) {
                throw ValidationException::withMessages(['periodo' => 'Este pago ya tiene una asignación a ese mes. No se duplicó.']);
            }

            $factura = DB::table('isp_mensualidades_generadas')->where('cliente_id', $pago->cliente_id)
                ->whereDate('periodo', $periodo)->first();
            if ($factura) {
                $cargo = DB::table('isp_cargos')->where('id', $factura->cargo_id)->lockForUpdate()->first();
                if (!$cargo) throw ValidationException::withMessages(['periodo' => 'No encontramos el cargo del mes.']);
                if (DB::table('isp_aplicaciones_pago')->where('pago_id', $pago->id)->where('cargo_id', $cargo->id)->exists()) {
                    throw ValidationException::withMessages(['periodo' => 'Este pago ya fue aplicado a esa factura.']);
                }
                $pendiente = $this->centavos($cargo->monto) - $this->centavos(
                    DB::table('isp_aplicaciones_pago')->where('cargo_id', $cargo->id)->sum('monto'));
                if ($centavos > $pendiente) {
                    throw ValidationException::withMessages(['monto' => 'Ese mes solo tiene pendiente S/ '.number_format(max(0,$pendiente)/100,2).'.']);
                }
                DB::table('isp_aplicaciones_pago')->insert([
                    'pago_id' => $pago->id, 'cargo_id' => $cargo->id,
                    'monto' => $this->dinero($centavos), 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                // Solo meses anteriores sin factura: no inventa una factura ni paga futuras automáticamente.
                if ($periodo >= today('America/Lima')->startOfMonth()->toDateString()) {
                    throw ValidationException::withMessages(['periodo' => 'El mes actual aún no tiene factura. Genérala primero; no marcaremos el mes como pagado sin factura.']);
                }
                DB::table('isp_repartos_periodos')->insert([
                    'cliente_id' => $pago->cliente_id, 'pago_id' => $pago->id,
                    'periodo' => $periodo, 'monto' => $this->dinero($centavos),
                    'observacion' => 'Asignación manual a mes histórico sin factura; pago original conservado',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }, 3);
        $this->monto = '';
        session()->flash('mensaje', 'Importe asignado al mes seleccionado. El ingreso original conserva su fecha.');
    }


    /**
     * Convierte una asignacion INFORMATIVA del mes en una aplicacion contable
     * a una deuda anterior que el administrador ya confirmó.
     * El pago original y la fecha de caja no se modifican.
     */
    public function conciliarReparto(int $repartoId, int $cargoId): void
    {
        DB::transaction(function () use ($repartoId, $cargoId) {
            $pago = DB::table('isp_pagos')->where('id', $this->pagoId)->first();
            abort_unless($pago && $pago->categoria === 'internet', 404);

            // La fila del cliente es el punto comun de bloqueo con otros cobros.
            DB::table('clientes')->where('id', $pago->cliente_id)->lockForUpdate()->first();
            $pago = DB::table('isp_pagos')->where('id', $this->pagoId)->lockForUpdate()->first();
            $reparto = DB::table('isp_repartos_periodos')
                ->where('id', $repartoId)->where('pago_id', $pago->id)
                ->where('cliente_id', $pago->cliente_id)->lockForUpdate()->first();
            if (!$reparto) {
                throw ValidationException::withMessages(['conciliacion' => 'Este reparto ya no existe o fue conciliado. Actualiza la pagina.']);
            }
            $cargo = DB::table('isp_cargos')
                ->where('id', $cargoId)->where('cliente_id', $pago->cliente_id)
                ->where('categoria', $pago->categoria)
                ->where('tipo', 'saldo_excel_verificado')->lockForUpdate()->first();
            if (!$cargo) {
                throw ValidationException::withMessages(['conciliacion' => 'Solo puedes aplicar el pago a una deuda anterior confirmada del mismo cliente.']);
            }
            $centavos = $this->centavos($reparto->monto);
            if ($centavos <= 0 ||
                DB::table('isp_auditoria_conciliaciones_repartos')->where('reparto_original_id', $reparto->id)->exists()) {
                throw ValidationException::withMessages(['conciliacion' => 'El reparto es invalido o ya fue utilizado.']);
            }
            $totalAplicacionesPago = $this->centavos(DB::table('isp_aplicaciones_pago')
                ->where('pago_id', $pago->id)->sum('monto'));
            $totalRepartosPago = $this->centavos(DB::table('isp_repartos_periodos')
                ->where('pago_id', $pago->id)->sum('monto'));
            if ($totalAplicacionesPago + $totalRepartosPago > $this->centavos($pago->monto)) {
                throw ValidationException::withMessages(['conciliacion' => 'Hay asignaciones superiores al dinero recibido. No se altero nada.']);
            }
            $aplicadoAlCargo = $this->centavos(DB::table('isp_aplicaciones_pago')
                ->where('cargo_id', $cargo->id)->sum('monto'));
            $pendiente = $this->centavos($cargo->monto) - $aplicadoAlCargo;
            if ($pendiente < $centavos) {
                throw ValidationException::withMessages([
                    'conciliacion' => 'El saldo seleccionado solo tiene S/ '.number_format(max(0, $pendiente) / 100, 2).' pendientes. No se desconto nada.',
                ]);
            }
            // La regla UNIQUE(pago_id,cargo_id) admite UNA aplicacion por pareja:
            // si ya hay una, sumamos el importe dentro del mismo bloqueo.
            $existente = DB::table('isp_aplicaciones_pago')
                ->where('pago_id', $pago->id)->where('cargo_id', $cargo->id)->lockForUpdate()->first();
            if ($existente) {
                DB::table('isp_aplicaciones_pago')->where('id', $existente->id)->update([
                    'monto' => $this->dinero($this->centavos($existente->monto) + $centavos),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('isp_aplicaciones_pago')->insert([
                    'pago_id' => $pago->id, 'cargo_id' => $cargo->id,
                    'monto' => $this->dinero($centavos), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            // Conservar AUDITORIA del reparto original. No contabilizarlo dos veces.
            DB::table('isp_auditoria_conciliaciones_repartos')->insert([
                'reparto_original_id' => $reparto->id,
                'cliente_id' => $pago->cliente_id, 'pago_id' => $pago->id,
                'cargo_id' => $cargo->id, 'periodo' => $reparto->periodo,
                'monto' => $this->dinero($centavos),
                'motivo' => 'Mes historico vinculado a saldo verificado; sin generar otro ingreso',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            // Al sustituir la asignacion informativa por la aplicacion real,
            // el mismo S/ 60 sigue contabilizado UNA SOLA VEZ.
            DB::table('isp_repartos_periodos')->where('id', $reparto->id)->delete();
        }, 3);
        session()->flash('mensaje', 'Pago aplicado a la deuda anterior confirmada. El ingreso original permanece intacto.');
    }

    private function centavos(mixed $monto): int
    {
        return (int) round(((float)$monto) * 100);
    }

    private function dinero(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    public function render()
    {
        $pago = DB::table('isp_pagos as p')->join('clientes as cl', 'cl.id', '=', 'p.cliente_id')
            ->where('p.id', $this->pagoId)
            ->select('p.*', 'cl.nombre as cliente_nombre', 'cl.codigo as cliente_codigo')->first();
        abort_unless($pago, 404);
        $cargos = DB::table('isp_aplicaciones_pago as a')
            ->join('isp_cargos as c', 'c.id', '=', 'a.cargo_id')
            ->leftJoin('isp_mensualidades_generadas as m', 'm.cargo_id', '=', 'c.id')
            ->where('a.pago_id', $pago->id)
            ->select('a.id', 'a.monto', 'c.descripcion', 'm.periodo')->get();
        $anteriores = DB::table('isp_repartos_periodos')->where('pago_id', $pago->id)
            ->orderBy('periodo')->get();
        $exoneraciones = DB::table('isp_exoneraciones_periodos')->where('cliente_id', $pago->cliente_id)
            ->orderBy('periodo')->get();
        // Solo permitir conciliar repartos historicos con cargos de SALDO EXCEL VERIFICADO.
        // Nunca aplicar historico a la mensualidad siguiente por accidente.
        $cargosVerificados = DB::table('isp_cargos')
            ->where('cliente_id', $pago->cliente_id)
            ->where('categoria', $pago->categoria)
            ->where('tipo', 'saldo_excel_verificado')
            ->orderBy('fecha_vencimiento')->get();
        foreach ($cargosVerificados as $cargo) {
            $usado = $this->centavos(DB::table('isp_aplicaciones_pago')
                ->where('cargo_id', $cargo->id)->sum('monto'));
            $cargo->pendiente = max(0, $this->centavos($cargo->monto) - $usado) / 100;
        }
        $cargosVerificados = $cargosVerificados->filter(fn ($cargo) => $cargo->pendiente > 0);
        $conciliaciones = DB::table('isp_auditoria_conciliaciones_repartos')
            ->where('pago_id', $pago->id)->orderBy('id')->get();
        $aplicado = $this->centavos($cargos->sum('monto')) + $this->centavos($anteriores->sum('monto'));
        $reservado = $this->centavos(DB::table('isp_anticipos_internet')->where('pago_id', $pago->id)
            ->selectRaw('COALESCE(SUM(monto - monto_aplicado), 0) as total')->value('total'));
        $sinAsignar = max(0, $this->centavos($pago->monto) - $aplicado - $reservado)/100;
        return view('livewire.cobranza.distribuir-pago', compact('pago','cargos','anteriores','exoneraciones','sinAsignar','cargosVerificados','conciliaciones'));
    }
}
