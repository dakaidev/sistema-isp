<?php

namespace App\Livewire\Cobranza;

use App\Models\Cliente;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** Un ingreso bancario, varios destinos mensuales, sin aplicar FIFO. */
class PagoAdelantado extends Component
{
    public int $clienteId;
    public string $importe = '';
    public string $operacionUuid = '';
    public string $fechaPago = '';
    public string $medio = 'efectivo';
    public string $referenciaPago = '';
    public string $observaciones = '';
    /** @var array<int,array{periodo:string,monto:string}> */
    public array $meses = [];

    public function mount(int $clienteId): void
    {
        Cliente::findOrFail($clienteId);
        $this->clienteId = $clienteId;
        $this->operacionUuid = (string) Str::uuid();
        $this->fechaPago = today('America/Lima')->toDateString();
        $this->meses = [['periodo' => today('America/Lima')->format('Y-m'), 'monto' => '']];
    }

    public function agregarMes(): void
    {
        if (count($this->meses) >= 12) {
            $this->addError('meses', 'Máximo 12 meses por cobro.');
            return;
        }
        $ultimo = collect($this->meses)->last()['periodo'] ?? today('America/Lima')->format('Y-m');
        $valido = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', (string) $ultimo);
        $siguiente = ($valido ? CarbonImmutable::createFromFormat('!Y-m', $ultimo, 'America/Lima')
            : CarbonImmutable::today('America/Lima'))->addMonth()->format('Y-m');
        $this->meses[] = ['periodo' => $siguiente, 'monto' => ''];
        $this->resetValidation();
    }

    public function quitarMes(int $index): void
    {
        if (count($this->meses) <= 1) return;
        if (!array_key_exists($index, $this->meses)) return;
        unset($this->meses[$index]);
        $this->meses = array_values($this->meses);
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'importe' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
            'fechaPago' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today('America/Lima')->toDateString()],
            'medio' => ['required', 'in:efectivo,yape,plin,transferencia,otro'],
            'referenciaPago' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'meses' => ['required', 'array', 'min:1', 'max:12'],
            'meses.*.periodo' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/D'],
            'meses.*.monto' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
        ]);
        $total = $this->centavos($datos['importe']);
        $partidas = [];
        $suma = 0;
        foreach ($datos['meses'] as $n => $item) {
            $periodo = $item['periodo'].'-01';
            $centavos = $this->centavos($item['monto']);
            if (isset($partidas[$periodo])) {
                throw ValidationException::withMessages(['meses' => 'No repitas el mes '.$item['periodo'].'.']);
            }
            $partidas[$periodo] = $centavos;
            $suma += $centavos;
        }
        if ($suma !== $total) {
            throw ValidationException::withMessages(['importe' => 'La suma de los meses es S/ '.$this->dinero($suma).'; debe coincidir exactamente con el pago recibido (S/ '.$this->dinero($total).').']);
        }

        DB::transaction(function () use ($datos, $partidas, $total) {
            Cliente::whereKey($this->clienteId)->lockForUpdate()->firstOrFail();
            if (DB::table('isp_anticipos_internet')->where('operacion_uuid', $this->operacionUuid)->exists()) {
                throw ValidationException::withMessages(['importe' => 'Este pago ya fue registrado. No vuelvas a guardarlo.']);
            }
            $config = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->clienteId)->first();
            $hoyMes = today('America/Lima')->startOfMonth()->toDateString();
            $limite = today('America/Lima')->startOfMonth()->addMonths(12)->toDateString();
            $destinos = [];

            // Comprobar TODOS los meses antes de crear el ingreso; un error cancela toda la operacion.
            foreach ($partidas as $periodo => $centavos) {
                if ($periodo > $limite) {
                    throw ValidationException::withMessages(['meses' => 'No reserves más de 12 meses hacia adelante.']);
                }
                if (DB::table('isp_exoneraciones_periodos')->where('cliente_id', $this->clienteId)
                    ->whereDate('periodo', $periodo)->exists()) {
                    throw ValidationException::withMessages(['meses' => 'El mes '.substr($periodo, 0, 7).' está exonerado. Revísalo antes de cobrar.']);
                }
                $factura = DB::table('isp_mensualidades_generadas')
                    ->where('cliente_id', $this->clienteId)->whereDate('periodo', $periodo)->first();
                $cargoManualEncontrado = false;
                // Un cargo creado desde "Cargo" no pertenece automaticamente a isp_mensualidades_generadas.
                // Para meses actuales/pasados reconocer UNICAMENTE un cargo manual de Internet
                // cuyo vencimiento caiga dentro del mes escogido. Nunca adivinar con varios cargos.
                if (!$factura && $periodo <= $hoyMes) {
                    $finDelMes = CarbonImmutable::parse($periodo, 'America/Lima')->endOfMonth()->toDateString();
                    $cargosManuales = DB::table('isp_cargos')
                        ->where('cliente_id', $this->clienteId)
                        ->where('categoria', 'internet')
                        ->where('tipo', 'cargo_manual')
                        ->whereBetween('fecha_vencimiento', [$periodo, $finDelMes])
                        ->whereNotIn('id', function ($consulta) {
                            $consulta->select('cargo_id')->from('isp_mensualidades_generadas');
                        })
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($cargosManuales->count() > 1) {
                        throw ValidationException::withMessages(['meses' =>
                            'Hay varios cargos manuales de Internet con vencimiento en '.substr($periodo, 0, 7).
                            '. No asignaremos el dinero al azar. Revisa los cargos y avísame para elegir uno.']);
                    }
                    if ($cargosManuales->count() === 1) {
                        $cargoManual = $cargosManuales->first();
                        // No cambiar silenciosamente el mes al que otro pago ya imputo este cargo.
                        $otroPeriodo = DB::table('isp_imputaciones_pagos_meses')
                            ->where('cargo_id', $cargoManual->id)->whereDate('periodo', '!=', $periodo)->exists();
                        if ($otroPeriodo) {
                            throw ValidationException::withMessages(['meses' =>
                                'El cargo manual de '.substr($periodo, 0, 7).
                                ' ya esta asociado a otro mes. Revisa sus pagos antes de continuar.']);
                        }
                        $factura = (object) ['cargo_id' => $cargoManual->id];
                        $cargoManualEncontrado = true;
                    }
                }
                if ($factura) {
                    $cargo = DB::table('isp_cargos')->where('id', $factura->cargo_id)
                        ->where('cliente_id', $this->clienteId)->where('categoria', 'internet')->lockForUpdate()->first();
                    if (!$cargo) {
                        throw ValidationException::withMessages(['meses' => 'No encontramos el cargo de '.substr($periodo, 0, 7).'.']);
                    }
                    $ocupado = $this->centavos(DB::table('isp_aplicaciones_pago')->where('cargo_id', $cargo->id)->sum('monto'));
                    $pendiente = max(0, $this->centavos($cargo->monto) - $ocupado);
                    if ($centavos > $pendiente) {
                        throw ValidationException::withMessages(['meses' => 'En '.substr($periodo, 0, 7).' solo quedan S/ '.$this->dinero($pendiente).' pendientes.']);
                    }
                    $destinos[$periodo] = [
                        'cargo_id' => (int) $cargo->id,
                        'manual' => $cargoManualEncontrado,
                        'monto_cargo' => $cargo->monto,
                    ];
                } else {
                    if ($periodo <= $hoyMes) {
                        throw ValidationException::withMessages(['meses' =>
                            'No encontre una mensualidad ni un cargo manual de Internet con vencimiento en '.substr($periodo, 0, 7).
                            '. Si ya creaste el cargo, comprueba su categoria y fecha de vencimiento. No lo dupliques.']);
                    }
                    if (!$config || !$config->habilitado || $config->tarifa_mensual === null || $config->tarifa_mensual <= 0) {
                        throw ValidationException::withMessages(['meses' => 'Primero configura y habilita la tarifa mensual del cliente para recibir adelantos.']);
                    }
                    $finMes = CarbonImmutable::parse($periodo, 'America/Lima')->endOfMonth()->toDateString();
                    if ($config->inicio_cobro > $finMes) {
                        throw ValidationException::withMessages(['meses' => 'El servicio no comienza todavía en '.substr($periodo, 0, 7).'.']);
                    }
                    $reservado = $this->centavos(DB::table('isp_anticipos_internet')
                        ->where('cliente_id', $this->clienteId)->whereDate('periodo', $periodo)->sum('monto'));
                    $tarifa = $this->centavos($config->tarifa_mensual);
                    if ($centavos + $reservado > $tarifa) {
                        throw ValidationException::withMessages(['meses' => 'Para '.substr($periodo, 0, 7).' la tarifa actual es S/ '.$this->dinero($tarifa).' y ya hay S/ '.$this->dinero($reservado).' reservados.']);
                    }
                    $destinos[$periodo] = ['cargo_id' => null];
                }
            }

            $pagoId = DB::table('isp_pagos')->insertGetId([
                'cliente_id' => $this->clienteId,
                'monto' => $this->dinero($total),
                'fecha_pago' => $datos['fechaPago'],
                'categoria' => 'internet',
                'medio' => $datos['medio'],
                'referencia' => trim($datos['referenciaPago'] ?? '') ?: null,
                'observaciones' => trim($datos['observaciones'] ?? '') ?: null,
                'usuario_id' => Auth::id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $primeraFila = true;
            foreach ($partidas as $periodo => $centavos) {
                $cargoId = $destinos[$periodo]['cargo_id'];
                if ($cargoId !== null) {
                    if ($destinos[$periodo]['manual'] ?? false) {
                        // Vincular ese cargo manual al mes sin crear otra deuda.
                        // Impide que el generador cree una segunda mensualidad para ese mes.
                        DB::table('isp_mensualidades_generadas')->insert([
                            'cliente_id' => $this->clienteId,
                            'cargo_id' => $cargoId,
                            'periodo' => $periodo,
                            'tarifa_base' => $destinos[$periodo]['monto_cargo'],
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    DB::table('isp_aplicaciones_pago')->insert([
                        'pago_id' => $pagoId, 'cargo_id' => $cargoId,
                        'monto' => $this->dinero($centavos), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('isp_anticipos_internet')->insert([
                    'cliente_id' => $this->clienteId,
                    'operacion_uuid' => $primeraFila ? $this->operacionUuid : null,
                    'pago_id' => $pagoId,
                    'cargo_id' => $cargoId,
                    'periodo' => $periodo,
                    'monto' => $this->dinero($centavos),
                    'monto_aplicado' => $this->dinero($cargoId !== null ? $centavos : 0),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $primeraFila = false;
            }
        }, 3);

        session()->flash('mensaje', 'Cobro de S/ '.$this->dinero($total).' guardado UNA vez y repartido entre '.count($partidas).' mes(es). Los meses futuros quedan reservados.');
        $this->redirectRoute('cobranza.ficha', ['cliente' => $this->clienteId]);
    }

    private function centavos(string|int|float $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    private function dinero(int $valor): string
    {
        return number_format($valor / 100, 2, '.', '');
    }

    public function render()
    {
        $cliente = Cliente::findOrFail($this->clienteId);
        $config = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->clienteId)->first();
        $previsto = 0;
        foreach ($this->meses as $fila) $previsto += $this->centavos($fila['monto'] ?? '0');
        return view('livewire.cobranza.pago-adelantado', [
            'cliente' => $cliente, 'config' => $config,
            'sumaAsignada' => $this->dinero($previsto),
            'diferencia' => $this->dinero($this->centavos($this->importe) - $previsto),
        ]);
    }
}
