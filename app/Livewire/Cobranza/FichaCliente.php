<?php

namespace App\Livewire\Cobranza;

use App\Models\Cliente;
use App\Support\IspAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** Ficha de pagos, cargos, adelantos e historial del sistema. */
class FichaCliente extends Component
{
    public function boot(): void
    {
        IspAccess::enforce('cobranza.view');
    }

    public int $clienteId;

    public string $formulario = '';

    /** Edicion puntual de un cargo ya emitido: no afecta ingresos. */
    public ?int $cargoCorreccionId = null;

    public string $cargoCorreccionMonto = '';

    public string $cargoCorreccionMotivo = '';

    public string $tarifa = '';

    public string $inicioCobro = '';

    public bool $habilitado = false;

    public string $importe = '';

    public string $categoria = 'internet';

    public string $fechaVencimiento = '';

    public string $fechaPago = '';

    public string $medio = 'efectivo';

    public string $referenciaPago = '';

    public string $observaciones = '';

    public string $mesPago = '';

    public string $destinoPago = '';

    public function mount(int $clienteId): void
    {
        Cliente::findOrFail($clienteId);
        $this->clienteId = $clienteId;
        $this->fechaPago = today('America/Lima')->toDateString();
        $this->mesPago = substr($this->fechaPago, 0, 7);
        $this->cargarTarifa();
    }

    private function cargarTarifa(): void
    {
        $cfg = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->clienteId)->first();
        $this->tarifa = $cfg && $cfg->tarifa_mensual !== null ? (string) $cfg->tarifa_mensual : '';
        $this->inicioCobro = $cfg?->inicio_cobro ?: today('America/Lima')->startOfMonth()->toDateString();
        $this->habilitado = $cfg ? (bool) $cfg->habilitado : false;
    }

    public function abrir(string $tipo): void
    {
        if ($tipo === 'pago') {
            IspAccess::enforce('cobranza.pay');
        }
        if ($tipo === 'tarifa' || $tipo === 'cargo') {
            IspAccess::enforce('tarifas.manage');
        }

        if (! in_array($tipo, ['tarifa', 'pago'], true)) {
            abort(400);
        }
        if ($tipo === 'pago') {
        } elseif ($tipo === 'pago') {
            $this->importe = '';
            $this->fechaPago = today('America/Lima')->toDateString();
            $this->mesPago = substr($this->fechaPago, 0, 7);
        } else {
            $this->cargarTarifa();
        }
        $this->fechaVencimiento = '';
        $this->categoria = 'internet';
        $this->medio = 'efectivo';
        $this->referenciaPago = '';
        $this->observaciones = '';
        $this->destinoPago = '';
        $this->resetValidation();
        $this->formulario = $tipo;
    }

    public function cerrar(): void
    {
        $this->formulario = '';
        $this->resetValidation();
    }

    /** Permite corregir un cargo mensual sin crear pagos ni descuentos ficticios. */
    public function abrirCorreccionCargo(int $cargoId): void
    {
        IspAccess::enforce('cobranza.adjust');

        $cargo = DB::table('isp_cargos')->where('id', $cargoId)
            ->where('cliente_id', $this->clienteId)->where('categoria', 'internet')
            ->whereIn('tipo', ['cargo_manual', 'mensualidad_automatica'])->first();
        if (! $cargo) {
            $this->addError('correccionCargo', 'No se encontro una mensualidad de Internet valida para corregir.');

            return;
        }
        $this->resetValidation();
        $this->formulario = '';
        $this->cargoCorreccionId = (int) $cargo->id;
        $this->cargoCorreccionMonto = number_format((float) $cargo->monto, 2, '.', '');
        $this->cargoCorreccionMotivo = '';
    }

    public function cerrarCorreccionCargo(): void
    {
        $this->cargoCorreccionId = null;
        $this->cargoCorreccionMonto = '';
        $this->cargoCorreccionMotivo = '';
        $this->resetValidation();
    }

    /** Guardar nueva cifra + razon, en la misma transaccion y con bloqueo por cliente. */
    public function guardarCorreccionCargoAuditada(): void
    {
        IspAccess::enforce('cobranza.adjust');

        if ($this->cargoCorreccionId === null) {
            abort(400);
        }
        $datos = $this->validate([
            'cargoCorreccionMonto' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
            'cargoCorreccionMotivo' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'cargoCorreccionMotivo.required' => 'Escribe por que corriges esta mensualidad.',
            'cargoCorreccionMotivo.min' => 'Escribe un motivo de al menos 10 caracteres.',
        ]);
        $cargoId = $this->cargoCorreccionId;
        $nuevoCentavos = $this->centavos($datos['cargoCorreccionMonto']);
        $motivo = trim($datos['cargoCorreccionMotivo']);
        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages(['cargoCorreccionMotivo' => 'Explica el motivo con al menos 10 caracteres.']);
        }
        [$antes, $despues] = DB::transaction(function () use ($cargoId, $nuevoCentavos, $motivo) {
            Cliente::whereKey($this->clienteId)->lockForUpdate()->firstOrFail();
            $cargo = DB::table('isp_cargos')->where('id', $cargoId)
                ->where('cliente_id', $this->clienteId)->where('categoria', 'internet')
                ->whereIn('tipo', ['cargo_manual', 'mensualidad_automatica'])
                ->lockForUpdate()->first();
            if (! $cargo) {
                throw ValidationException::withMessages(['correccionCargo' => 'El cargo ya no existe o no se puede corregir.']);
            }
            $antes = $this->centavos($cargo->monto);
            if ($antes === $nuevoCentavos) {
                throw ValidationException::withMessages(['cargoCorreccionMonto' => 'El importe nuevo debe ser diferente del original.']);
            }
            $aplicaciones = DB::table('isp_aplicaciones_pago')->where('cargo_id', $cargoId)
                ->lockForUpdate()->get(['monto']);
            $pagado = 0;
            foreach ($aplicaciones as $aplicacion) {
                $pagado += $this->centavos($aplicacion->monto);
            }
            if ($nuevoCentavos < $pagado) {
                throw ValidationException::withMessages(['cargoCorreccionMonto' => 'Este cargo tiene S/ '.number_format($pagado / 100, 2).' ya pagados. No puedes bajarlo por debajo de lo aplicado.']);
            }
            DB::table('isp_cargos')->where('id', $cargoId)->update([
                'monto' => $this->dinero($nuevoCentavos),
                'updated_at' => now(),
            ]);
            // Registro permanente del motivo, autor e importe anterior y nuevo.
            DB::table('isp_ajustes_cargos')->insert([
                'cliente_id' => $this->clienteId,
                'cargo_id' => $cargoId,
                'usuario_id' => Auth::id(),
                'monto_anterior' => $this->dinero($antes),
                'monto_nuevo' => $this->dinero($nuevoCentavos),
                'motivo' => $motivo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$antes, $nuevoCentavos];
        }, 3);
        $this->cerrarCorreccionCargo();
        session()->flash('mensaje', 'Cargo corregido: S/ '.number_format($antes / 100, 2).
            ' a S/ '.number_format($despues / 100, 2).
            '. Motivo guardado en el historial del cargo. No se registro ningun pago.');
    }

    public function guardarTarifa(): void
    {
        IspAccess::enforce('tarifas.manage');

        if ($this->formulario !== 'tarifa') {
            abort(400);
        }
        $datos = $this->validate([
            'tarifa' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
            'inicioCobro' => ['required', 'date_format:Y-m-d'],
            'habilitado' => ['boolean'],
        ]);
        DB::transaction(function () use ($datos) {
            Cliente::whereKey($this->clienteId)->lockForUpdate()->firstOrFail();
            $configuracion = DB::table('isp_configuracion_mensual')
                ->where('cliente_id', $this->clienteId)->first();
            $values = [
                'tarifa_mensual' => number_format((float) $datos['tarifa'], 2, '.', ''),
                'inicio_cobro' => $datos['inicioCobro'],
                'habilitado' => (bool) $datos['habilitado'],
                'updated_at' => now(),
            ];
            if ($configuracion) {
                DB::table('isp_configuracion_mensual')
                    ->where('cliente_id', $this->clienteId)->update($values);
            } else {
                DB::table('isp_configuracion_mensual')->insert([
                    'cliente_id' => $this->clienteId,
                    'created_at' => now(),
                ] + $values);
            }
        });
        $this->cerrar();
        session()->flash('mensaje', 'Mensualidad guardada. Los cargos anteriores no se modificaron.');
    }

    public function updatedCategoria(): void
    {
        if ($this->formulario === 'pago') {
            $this->destinoPago = '';
        }
    }

    /** Registra dinero recibido y su destino en UNA transaccion; jamas aplica FIFO. */
    public function guardarPago(): void
    {
        IspAccess::enforce('cobranza.pay');

        if ($this->formulario !== 'pago') {
            abort(400);
        }
        $datos = $this->validate([
            'importe' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
            'fechaPago' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today('America/Lima')->toDateString()],
            'mesPago' => ['nullable', 'date_format:Y-m'],
            'medio' => ['required', 'in:efectivo,yape,plin,transferencia,otro'],
            'categoria' => ['required', 'in:internet,equipo,otros'],
            'destinoPago' => ['required', 'string', 'max:100'],
            'referenciaPago' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);
        // ULTRA_PERIODO_POR_CARGO_V1: la fecha del cobro no define el mes pagado.
        $centavos = $this->centavos($datos['importe']);
        $destino = $datos['destinoPago'];
        DB::transaction(function () use ($datos, $centavos, $destino) {
            $mes = null;
            Cliente::whereKey($this->clienteId)->lockForUpdate()->firstOrFail();
            $cargo = null;
            if (preg_match('/^cargo:([1-9]\d*)$/D', $destino, $m)) {
                $cargo = DB::table('isp_cargos')->where('id', (int) $m[1])
                    ->where('cliente_id', $this->clienteId)
                    ->where('categoria', $datos['categoria'])->lockForUpdate()->first();
                if (! $cargo) {
                    throw ValidationException::withMessages(['destinoPago' => 'La deuda no existe o pertenece a otra categoría.']);
                }
                $periodoGenerado = DB::table('isp_mensualidades_generadas')->where('cargo_id', $cargo->id)->first();
                if ($datos['categoria'] === 'internet') {
                    if ($periodoGenerado) {
                        // La factura automatica tiene un periodo oficial independiente del cobro.
                        $periodoFuente = (string) $periodoGenerado->periodo;
                        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])-\d{2}$/D', substr($periodoFuente, 0, 10))) {
                            throw ValidationException::withMessages(['destinoPago' => 'Esta factura no tiene un mes de servicio valido. Revisala antes de cobrar.']);
                        }
                        $mes = substr($periodoFuente, 0, 7).'-01';
                    } else {
                        // ULTRA_MES_MANUAL_CLARO_V1: un vencimiento no prueba el mes del servicio.
                        $mes = $this->mesCargoManualInternet(
                            (string) $cargo->descripcion,
                            $cargo->fecha_vencimiento !== null ? (string) $cargo->fecha_vencimiento : null
                        );
                    }
                }
                $yaAplicado = $this->centavos(DB::table('isp_aplicaciones_pago')
                    ->where('cargo_id', $cargo->id)->sum('monto'));
                $pendiente = max(0, $this->centavos($cargo->monto) - $yaAplicado);
                if ($centavos > $pendiente) {
                    throw ValidationException::withMessages([
                        'importe' => 'El saldo seleccionado solo tiene S/ '.number_format($pendiente / 100, 2).' pendiente(s). Para un pago de varios conceptos registra los importes por separado.',
                    ]);
                }
            } elseif ($destino === 'historial') {
                if ($datos['categoria'] !== 'internet') {
                    throw ValidationException::withMessages(['destinoPago' => 'El router/equipo necesita una deuda registrada para aplicar el pago.']);
                }
                if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', (string) ($datos['mesPago'] ?? ''))) {
                    throw ValidationException::withMessages(['mesPago' => 'Selecciona el mes histórico que corresponde al pago.']);
                }
                $mes = $datos['mesPago'].'-01';
                if ($mes >= today('America/Lima')->startOfMonth()->toDateString()) {
                    throw ValidationException::withMessages(['mesPago' => 'Para el mes actual o futuro crea o selecciona primero su mensualidad.']);
                }
                $existeFacturado = DB::table('isp_mensualidades_generadas')
                    ->where('cliente_id', $this->clienteId)->whereDate('periodo', $mes)->exists();
                if ($existeFacturado) {
                    throw ValidationException::withMessages(['destinoPago' => 'Este mes tiene una factura. Selecciona el cargo correspondiente.']);
                }
                // Evita registrar un historico si ya hay un cargo de ese mismo mes.
                $cargosDelMes = DB::table('isp_cargos')
                    ->where('cliente_id', $this->clienteId)
                    ->where('categoria', 'internet')
                    ->where('fecha_vencimiento', '>=', $mes)
                    ->where('fecha_vencimiento', '<', Carbon::parse($mes)->addMonth()->toDateString())
                    ->get(['id', 'monto']);
                foreach ($cargosDelMes as $cargoDelMes) {
                    throw ValidationException::withMessages([
                        'destinoPago' => 'Este mes ya tiene un cargo registrado: utiliza el historial o el cargo existente; no crees un segundo pago histórico.',
                    ]);
                }

                // Si existe una deuda anterior confirmada, debe tener prioridad frente a anotaciones sin cargo.
                $pendienteVerificado = DB::table('isp_cargos')->where('cliente_id', $this->clienteId)
                    ->where('categoria', 'internet')->where('tipo', 'saldo_excel_verificado')->get();
                foreach ($pendienteVerificado as $item) {
                    $pendiente = $this->centavos($item->monto) - $this->centavos(DB::table('isp_aplicaciones_pago')
                        ->where('cargo_id', $item->id)->sum('monto'));
                    if ($pendiente > 0) {
                        throw ValidationException::withMessages(['destinoPago' => 'Hay una deuda anterior confirmada pendiente. Selecciónala para descontar este pago realmente.']);
                    }
                }
            } else {
                throw ValidationException::withMessages(['destinoPago' => 'Selecciona una deuda o un mes histórico antes de registrar.']);
            }

            $pagoId = DB::table('isp_pagos')->insertGetId([
                'cliente_id' => $this->clienteId,
                'monto' => $this->dinero($centavos),
                'fecha_pago' => $datos['fechaPago'],
                'categoria' => $datos['categoria'],
                'medio' => $datos['medio'],
                'referencia' => trim($datos['referenciaPago']) ?: null,
                'observaciones' => trim($datos['observaciones']) ?: null,
                'usuario_id' => Auth::id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($cargo) {
                DB::table('isp_aplicaciones_pago')->insert([
                    'pago_id' => $pagoId, 'cargo_id' => $cargo->id,
                    'monto' => $this->dinero($centavos), 'created_at' => now(), 'updated_at' => now(),
                ]);
                if (! DB::table('isp_mensualidades_generadas')->where('cargo_id', $cargo->id)->exists()
                    && $datos['categoria'] === 'internet' && $mes !== null) {
                    DB::table('isp_imputaciones_pagos_meses')->insert([
                        'cliente_id' => $this->clienteId, 'pago_id' => $pagoId,
                        'cargo_id' => $cargo->id, 'periodo' => $mes,
                        'monto' => $this->dinero($centavos),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('isp_repartos_periodos')->insert([
                    'cliente_id' => $this->clienteId, 'pago_id' => $pagoId,
                    'periodo' => $mes, 'monto' => $this->dinero($centavos),
                    'observacion' => 'Pago historico sin factura; requiere conciliacion si existe deuda adicional',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }, 3);
        $this->cerrar();
        session()->flash('mensaje', 'Pago guardado y asignado en una sola operación. La fecha de ingreso se conservó.');
    }

    /** Mismo criterio que la pantalla de Cobranza: FIFO por categoría, sin mezclar internet/equipos. */
    private function aplicarPagosPendientes(): void
    {
        foreach (['internet', 'equipo', 'otros'] as $categoria) {
            $cargos = DB::table('isp_cargos')->where('cliente_id', $this->clienteId)->where('categoria', $categoria)
                ->orderBy('fecha_vencimiento')->orderBy('id')->get(['id', 'monto']);
            $pagos = DB::table('isp_pagos')->where('cliente_id', $this->clienteId)->where('categoria', $categoria)
                ->orderBy('fecha_pago')->orderBy('id')->get(['id', 'monto', 'fecha_pago']);
            // Mes facturado de cada mensualidad automatica: no usar ingresos anteriores.
            $periodos = DB::table('isp_mensualidades_generadas')
                ->where('cliente_id', $this->clienteId)->pluck('periodo', 'cargo_id');
            $pendientes = [];
            foreach ($cargos as $cargo) {
                $usado = $this->centavos(DB::table('isp_aplicaciones_pago')->where('cargo_id', $cargo->id)->sum('monto'));
                $pendientes[$cargo->id] = max(0, $this->centavos($cargo->monto) - $usado);
            }
            foreach ($pagos as $pago) {
                $usado = $this->centavos(DB::table('isp_aplicaciones_pago')->where('pago_id', $pago->id)->sum('monto'));
                $disponible = max(0, $this->centavos($pago->monto) - $usado);
                foreach ($pendientes as $cargoId => $pendiente) {
                    if ($disponible === 0) {
                        break;
                    }
                    if ($pendiente === 0) {
                        continue;
                    }
                    // Un pago de septiembre nunca cancela octubre de forma automatica.
                    if (isset($periodos[$cargoId]) && $pago->fecha_pago < $periodos[$cargoId]) {
                        continue;
                    }
                    $aplicar = min($disponible, $pendiente);
                    DB::table('isp_aplicaciones_pago')->insert([
                        'pago_id' => $pago->id, 'cargo_id' => $cargoId, 'monto' => $this->dinero($aplicar),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $pendientes[$cargoId] -= $aplicar;
                    $disponible -= $aplicar;
                }
            }
        }
    }

    /** Mes del servicio SOLO para cargos manuales inequívocos; null en los demás casos. */
    private function mesCargoManualInternet(string $descripcion, ?string $fechaVencimiento): ?string
    {
        if ($fechaVencimiento === null || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])-\d{2}$/D', $fechaVencimiento)) {
            return null;
        }

        // "Debe muchos meses menos mayo" NO significa que deba mayo ni octubre.
        if (preg_match('/\b(?:meses|mese|varios?|muchos?|menos|excepto|salvo|acumulados?|acumuladas?|anteriores?|sin)\b/iu', $descripcion)) {
            return null;
        }

        $patron = '/\b(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|settiembre|octubre|noviembre|diciembre)\b/iu';
        if (preg_match_all($patron, $descripcion, $coincidencias) !== 1) {
            return null;
        }

        $numero = match (mb_strtolower($coincidencias[1][0], 'UTF-8')) {
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre', 'setiembre', 'settiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,
            default => null,
        };

        if ($numero === null || $numero !== (int) substr($fechaVencimiento, 5, 2)) {
            return null;
        }

        preg_match_all('/\b(?:19|20)\d{2}\b/u', $descripcion, $anios);
        foreach ($anios[0] as $anio) {
            if ($anio !== substr($fechaVencimiento, 0, 4)) {
                return null;
            }
        }

        return substr($fechaVencimiento, 0, 7).'-01';
    }

    private function centavos(string|int|float $monto): int
    {
        return (int) round((float) $monto * 100);
    }

    private function dinero(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    public function render()
    {
        $cliente = Cliente::findOrFail($this->clienteId);
        $config = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->clienteId)->first();
        $cargos = DB::table('isp_cargos')->where('cliente_id', $this->clienteId)
            ->orderByDesc('fecha_vencimiento')->orderByDesc('id')->get();
        $pagos = DB::table('isp_pagos')->where('cliente_id', $this->clienteId)
            ->orderByDesc('fecha_pago')->orderByDesc('id')->get();
        // Solo consulta: notas mensuales anteriores, SIN crear pagos ni sumar ingresos.
        $historicoMensual = DB::table('isp_registros_mensuales_historicos')
            ->where('cliente_id', $this->clienteId)
            ->where('importe', '>', 0)
            ->orderByDesc('periodo')->get();
        $aplicaciones = DB::table('isp_aplicaciones_pago')
            ->whereIn('cargo_id', $cargos->pluck('id'))->get()->groupBy('cargo_id');
        $totalAplicado = 0;
        $saldo = 0;
        $internet = 0;
        $equipo = 0;
        $vencido = 0;
        $hoy = today('America/Lima')->toDateString();
        $cargosConSaldo = [];
        foreach ($cargos as $cargo) {
            $aplicado = 0;
            foreach ($aplicaciones->get($cargo->id, collect()) as $a) {
                $aplicado += $this->centavos($a->monto);
            }
            $totalAplicado += $aplicado;
            $restante = max(0, $this->centavos($cargo->monto) - $aplicado);
            $saldo += $restante;
            if ($cargo->categoria === 'internet') {
                $internet += $restante;
                if ($cargo->fecha_vencimiento < $hoy) {
                    $vencido += $restante;
                }
            }
            if ($cargo->categoria === 'equipo') {
                $equipo += $restante;
            }
            $cargosConSaldo[] = ['cargo' => $cargo, 'pendiente' => $restante / 100];
        }
        $mesesGenerados = DB::table('isp_mensualidades_generadas')
            ->where('cliente_id', $this->clienteId)->get()->keyBy('cargo_id');
        $opcionesPago = [];
        if ($this->formulario === 'pago') {
            foreach ($cargosConSaldo as $item) {
                $cargo = $item['cargo'];
                if ($cargo->categoria !== $this->categoria || $item['pendiente'] <= 0) {
                    continue;
                }
                $mensualidad = $mesesGenerados->get($cargo->id);
                if ($mensualidad) {
                    $label = 'Internet '.substr((string) $mensualidad->periodo, 0, 7);
                } else {
                    $label = (string) $cargo->descripcion;
                    if ($cargo->categoria === 'internet') {
                        $mesManual = $this->mesCargoManualInternet(
                            $label,
                            $cargo->fecha_vencimiento !== null ? (string) $cargo->fecha_vencimiento : null
                        );
                        $label .= $mesManual
                            ? ' ('.substr($mesManual, 0, 7).')'
                            : ' (deuda manual: mes no identificado)';
                    }
                }
                $opcionesPago[] = ['valor' => 'cargo:'.$cargo->id,
                    'texto' => $label.' — pendiente S/ '.number_format($item['pendiente'], 2)];
            }
            if ($this->categoria === 'internet') {
                $opcionesPago[] = [
                    'valor' => 'historial',
                    'texto' => 'Registrar mes histórico anterior sin factura (caso especial)',
                ];
            }
        }
        $pagado = 0;
        foreach ($pagos as $pago) {
            $pagado += $this->centavos($pago->monto);
        }
        $pagosDistribuidosHistoricos = $this->centavos(DB::table('isp_repartos_periodos')
            ->where('cliente_id', $this->clienteId)->sum('monto'));
        $anticiposSinAplicar = DB::table('isp_anticipos_internet')->where('cliente_id', $this->clienteId)
            ->selectRaw('COALESCE(SUM(monto - monto_aplicado), 0) as saldo')->value('saldo');
        $resumen = [
            'saldo' => $saldo / 100,
            'internet' => $internet / 100,
            'equipo' => $equipo / 100,
            'vencido' => $vencido / 100,
            'credito' => max(0, $pagado - $totalAplicado - $pagosDistribuidosHistoricos - $this->centavos($anticiposSinAplicar)) / 100,
        ];
        $repartos = DB::table('isp_repartos_periodos')->where('cliente_id', $this->clienteId)->get()->groupBy('pago_id');
        $conciliaciones = DB::table('isp_auditoria_conciliaciones_repartos')
            ->where('cliente_id', $this->clienteId)->get()->groupBy('pago_id');
        $imputacionesDirectas = DB::table('isp_imputaciones_pagos_meses')
            ->where('cliente_id', $this->clienteId)->get()->groupBy('pago_id');
        $anticipos = DB::table('isp_anticipos_internet')->where('cliente_id', $this->clienteId)->orderBy('periodo')->get();
        // Solo lectura: cargos realmente cubiertos por cada pago.
        $aplicacionesConCargo = DB::table('isp_aplicaciones_pago as a')
            ->join('isp_pagos as p', 'p.id', '=', 'a.pago_id')
            ->join('isp_cargos as c', 'c.id', '=', 'a.cargo_id')
            ->leftJoin('isp_mensualidades_generadas as m', 'm.cargo_id', '=', 'c.id')
            ->where('p.cliente_id', $this->clienteId)
            ->where('c.cliente_id', $this->clienteId)
            ->select('a.pago_id', 'a.cargo_id', 'a.monto',
                'c.descripcion', 'c.fecha_vencimiento', 'm.periodo',
                'c.monto as cargo_monto', 'c.categoria as cargo_categoria')
            ->get()
            ->groupBy('pago_id');

        // SOLO LECTURA: pagos que ya estan aplicados a facturas mensuales reales.
        // La fecha de ingreso sigue en isp_pagos; el mes cubierto lo indica la factura.
        // No crea pagos, aplicaciones ni nuevas mensualidades.
        $pagosAplicadosAFacturas = DB::table('isp_aplicaciones_pago as a')
            ->join('isp_pagos as p', 'p.id', '=', 'a.pago_id')
            ->join('isp_mensualidades_generadas as m', 'm.cargo_id', '=', 'a.cargo_id')
            ->join('isp_cargos as c', 'c.id', '=', 'a.cargo_id')
            ->where('p.cliente_id', $this->clienteId)
            ->where('m.cliente_id', $this->clienteId)
            ->where('c.cliente_id', $this->clienteId)
            ->where('p.categoria', 'internet')
            ->where('c.categoria', 'internet')
            ->select('a.pago_id', 'a.cargo_id', 'a.monto', 'p.fecha_pago', 'm.periodo', 'c.monto as cargo_monto')
            ->orderBy('m.periodo')
            ->get();
        $exoneraciones = DB::table('isp_exoneraciones_periodos')->where('cliente_id', $this->clienteId)
            ->orderBy('periodo')->get();
        $historialCorrecciones = DB::table('isp_ajustes_cargos')
            ->where('cliente_id', $this->clienteId)
            ->orderByDesc('id')->get()->groupBy('cargo_id');

        return view('livewire.cobranza.ficha-cliente', compact(
            'cliente', 'config', 'cargosConSaldo', 'pagos', 'historicoMensual', 'resumen', 'repartos', 'conciliaciones', 'exoneraciones', 'opcionesPago', 'imputacionesDirectas', 'historialCorrecciones', 'anticipos', 'pagosAplicadosAFacturas', 'aplicacionesConCargo'
        ));
    }
}
