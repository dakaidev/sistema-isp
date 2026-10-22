<?php

namespace App\Livewire\Cobranza;

use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Gestion extends Component
{
    use WithPagination;

    public string $buscar = '';
    public string $filtro = 'todos';
    public ?int $clienteSeleccionado = null;
    public string $nombreSeleccionado = '';
    public string $accion = '';
    public string $importe = '';
    public string $categoria = 'internet';
    public string $concepto = '';
    public string $fechaVencimiento = '';
    public string $fechaPago = '';
    public string $medio = 'efectivo';
    public string $referenciaPago = '';
    public string $observaciones = '';

    public function updatedFiltro(): void
    {
        $this->resetPage();
        $this->cerrar();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
        $this->cerrar();
    }

    public function abrir(int $clienteId, string $accion): void
    {
        if (!in_array($accion, ['cargo', 'pago', 'historial'], true)) {
            abort(400, 'Acción no permitida.');
        }
        // Todos los pagos nuevos se registran desde la ficha, con mes y deuda elegidos en un paso.
        if ($accion === 'pago') {
            $this->redirectRoute('cobranza.ficha', ['cliente' => $clienteId]);
            return;
        }
        $cliente = Cliente::findOrFail($clienteId);
        $this->resetValidation();
        $this->clienteSeleccionado = $cliente->id;
        $this->nombreSeleccionado = $cliente->codigo . ' — ' . $cliente->nombre;
        $this->accion = $accion;
        $this->importe = '';
        $this->concepto = '';
        $this->fechaVencimiento = '';
        $this->fechaPago = today('America/Lima')->toDateString();
        $this->medio = 'efectivo';
        $this->categoria = 'internet';
        $this->referenciaPago = '';
        $this->observaciones = '';
    }

    public function cerrar(): void
    {
        $this->reset([
            'clienteSeleccionado', 'nombreSeleccionado', 'accion', 'importe',
            'concepto', 'fechaVencimiento', 'fechaPago', 'referenciaPago', 'observaciones',
        ]);
        $this->medio = 'efectivo';
        $this->categoria = 'internet';
        $this->resetValidation();
    }

    public function guardarCargo(): void
    {
        if ($this->accion !== 'cargo' || $this->clienteSeleccionado === null) {
            abort(400);
        }
        $datos = $this->validate([
            'importe' => 'required|numeric|decimal:0,2|between:0.01,99999999.99',
            'concepto' => 'required|string|min:4|max:255',
            'categoria' => 'required|in:internet,equipo,otros',
            'fechaVencimiento' => 'required|date_format:Y-m-d',
        ]);
        DB::transaction(function () use ($datos) {
            $id = $this->clienteSeleccionado;
            Cliente::whereKey($id)->lockForUpdate()->firstOrFail();
            DB::table('isp_cargos')->insert([
                'cliente_id' => $id,
                'servicio_id' => null,
                'tipo' => 'cargo_manual',
                'categoria' => $datos['categoria'],
                'descripcion' => trim($datos['concepto']),
                'monto' => $this->formatoDinero($this->aCentavos($datos['importe'])),
                'fecha_vencimiento' => $datos['fechaVencimiento'],
                'usuario_id' => Auth::id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        $this->cerrar();
        session()->flash('mensaje', 'Cargo verificado registrado. No se creó ningún servicio.');
    }

    public function guardarPago(): void
    {
        if ($this->accion !== 'pago' || $this->clienteSeleccionado === null) {
            abort(400);
        }
        $datos = $this->validate([
            'importe' => 'required|numeric|decimal:0,2|between:0.01,99999999.99',
            'fechaPago' => 'required|date_format:Y-m-d|before_or_equal:' . today('America/Lima')->toDateString(),
            'medio' => 'required|in:efectivo,yape,plin,transferencia,otro',
            'categoria' => 'required|in:internet,equipo,otros',
            'referenciaPago' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:1000',
        ]);
        DB::transaction(function () use ($datos) {
            $id = $this->clienteSeleccionado;
            Cliente::whereKey($id)->lockForUpdate()->firstOrFail();
            DB::table('isp_pagos')->insert([
                'cliente_id' => $id,
                'monto' => $this->formatoDinero($this->aCentavos($datos['importe'])),
                'fecha_pago' => $datos['fechaPago'],
                'categoria' => $datos['categoria'],
                'medio' => $datos['medio'],
                'referencia' => trim($datos['referenciaPago']) ?: null,
                'observaciones' => trim($datos['observaciones']) ?: null,
                'usuario_id' => Auth::id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        $this->cerrar();
        session()->flash('mensaje', 'Ingreso recibido y registrado. Ahora distribuye el pago por meses desde la ficha completa del cliente.');
    }

    /** Con la fila del cliente bloqueada, repartir pagos/anticipos a los cargos más antiguos. */
    private function aplicarPagosPendientes(int $clienteId): void
    {
        foreach (['internet', 'equipo', 'otros'] as $categoria) {
            $this->aplicarPagosCategoria($clienteId, $categoria);
        }
    }

    private function aplicarPagosCategoria(int $clienteId, string $categoria): void
    {
        $cargos = DB::table('isp_cargos')->where('cliente_id', $clienteId)->where('categoria', $categoria)
            ->orderBy('fecha_vencimiento')->orderBy('id')->get(['id', 'monto']);
        $pagos = DB::table('isp_pagos')->where('cliente_id', $clienteId)->where('categoria', $categoria)
            ->orderBy('fecha_pago')->orderBy('id')->get(['id', 'monto', 'fecha_pago']);
        $periodos = DB::table('isp_mensualidades_generadas')
            ->where('cliente_id', $clienteId)->pluck('periodo', 'cargo_id');

        $pendientes = [];
        foreach ($cargos as $cargo) {
            $yaAplicado = $this->aCentavos(DB::table('isp_aplicaciones_pago')
                ->where('cargo_id', $cargo->id)->sum('monto'));
            $pendientes[$cargo->id] = max(0, $this->aCentavos($cargo->monto) - $yaAplicado);
        }
        foreach ($pagos as $pago) {
            $yaAplicado = $this->aCentavos(DB::table('isp_aplicaciones_pago')
                ->where('pago_id', $pago->id)->sum('monto'));
            $disponible = max(0, $this->aCentavos($pago->monto) - $yaAplicado);
            if ($disponible === 0) {
                continue;
            }
            foreach ($pendientes as $cargoId => $pendiente) {
                if ($pendiente === 0) {
                    continue;
                }
                // No cruzar pagos anteriores con facturas de meses posteriores.
                if (isset($periodos[$cargoId]) && $pago->fecha_pago < $periodos[$cargoId]) {
                    continue;
                }
                $aplicar = min($disponible, $pendiente);
                // No hay dos pagos aplicados a un cargo duplicados; un pago/cargo = una fila.
                DB::table('isp_aplicaciones_pago')->insert([
                    'pago_id' => $pago->id, 'cargo_id' => $cargoId,
                    'monto' => $this->formatoDinero($aplicar),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $pendientes[$cargoId] -= $aplicar;
                $disponible -= $aplicar;
                if ($disponible === 0) {
                    break;
                }
            }
        }
    }

    private function aCentavos(string|int|float $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    private function formatoDinero(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }

    /** Los saldos son exclusivamente los cargos vigentes y los pagos aplicados. */
    private function resumen(int $clienteId): array
    {
        $cargos = DB::table('isp_cargos')->where('cliente_id', $clienteId)->get(['id', 'monto', 'fecha_vencimiento', 'categoria']);
        $pagos = DB::table('isp_pagos')->where('cliente_id', $clienteId)->sum('monto');
        $saldo = 0;
        $vencido = 0; // Solo internet, nunca deuda de router/equipos.
        $saldoInternet = 0;
        $saldoEquipo = 0;
        $aplicadoTotal = 0;
        $hoy = today('America/Lima')->toDateString();
        foreach ($cargos as $cargo) {
            $aplicado = $this->aCentavos(DB::table('isp_aplicaciones_pago')
                ->where('cargo_id', $cargo->id)->sum('monto'));
            $resto = max(0, $this->aCentavos($cargo->monto) - $aplicado);
            $aplicadoTotal += $aplicado;
            $saldo += $resto;
            if ($cargo->categoria === 'internet') {
                $saldoInternet += $resto;
                if ($cargo->fecha_vencimiento < $hoy) {
                    $vencido += $resto;
                }
            } elseif ($cargo->categoria === 'equipo') {
                $saldoEquipo += $resto;
            }
        }
        $repartosMeses = $this->aCentavos(DB::table('isp_repartos_periodos')
            ->where('cliente_id', $clienteId)->sum('monto'));
        $credito = max(0, $this->aCentavos($pagos) - $aplicadoTotal - $repartosMeses);
        return [
            'saldo' => $saldo / 100,
            'saldo_internet' => $saldoInternet / 100,
            'saldo_equipo' => $saldoEquipo / 100,
            'vencido' => $vencido / 100,
            'credito' => $credito / 100,
            'cargos' => $cargos->count(),
        ];
    }

    public function render()
    {
        $hoy = today('America/Lima')->toDateString();
        $clientes = Cliente::query()
            ->when($this->filtro === 'pagos', fn ($q) => $q->whereIn('id',
                DB::table('isp_pagos')->select('cliente_id')))
            ->when($this->filtro === 'vencidos', function ($q) use ($hoy) {
                $q->whereExists(function ($s) use ($hoy) {
                    $s->selectRaw('1')->from('isp_cargos as c')
                        ->whereColumn('c.cliente_id', 'clientes.id')
                        ->where('c.categoria', 'internet')
                        ->where('c.fecha_vencimiento', '<', $hoy)
                        ->whereRaw('c.monto > (SELECT COALESCE(SUM(a.monto), 0) FROM isp_aplicaciones_pago a WHERE a.cargo_id = c.id)');
                });
            })
            ->when(trim($this->buscar) !== '', function ($query) {
                $busqueda = trim($this->buscar);
                $query->where(function ($q) use ($busqueda) {
                    $q->where('codigo', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('telefono', 'like', "%{$busqueda}%");
                });
            })->orderBy('nombre')->paginate(15);
        $informacion = [];
        foreach ($clientes as $cliente) {
            $informacion[$cliente->id] = [
                'resumen' => $this->resumen($cliente->id),
                'ultimo_pago' => DB::table('isp_pagos')
                    ->where('cliente_id', $cliente->id)
                    ->orderByDesc('fecha_pago')->orderByDesc('id')->first(),
            ];
        }
        return view('livewire.cobranza.gestion', [
            'clientes' => $clientes,
            'informacion' => $informacion,
            'historialPagos' => $this->accion === 'historial' && $this->clienteSeleccionado !== null
                ? DB::table('isp_pagos')->where('cliente_id', $this->clienteSeleccionado)
                    ->orderByDesc('fecha_pago')->orderByDesc('id')->limit(40)->get()
                : collect(),
            'historialCargos' => $this->accion === 'historial' && $this->clienteSeleccionado !== null
                ? DB::table('isp_cargos')->where('cliente_id', $this->clienteSeleccionado)
                    ->orderByDesc('fecha_vencimiento')->orderByDesc('id')->limit(40)->get()
                : collect(),
        ]);
    }
}
