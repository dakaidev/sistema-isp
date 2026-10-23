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
    public string $filtroSede = 'todas';
    public string $filtroConexion = 'todos';
    public string $filtroFacturacion = 'todos';
    public string $filtroDeuda = 'todos';
    public string $mes = '';

    public function mount(): void
    {
        $this->mes = today('America/Lima')->format('Y-m');
    }

    public function updatedFiltroSede(): void { $this->resetPage(); $this->cerrar(); }
    public function updatedFiltroConexion(): void { $this->resetPage(); $this->cerrar(); }
    public function updatedFiltroFacturacion(): void { $this->resetPage(); $this->cerrar(); }
    public function updatedFiltroDeuda(): void { $this->resetPage(); $this->cerrar(); }
    public function updatedMes(): void { $this->resetPage(); $this->cerrar(); }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->filtroSede = 'todas';
        $this->filtroConexion = 'todos';
        $this->filtroFacturacion = 'todos';
        $this->filtroDeuda = 'todos';
        $this->filtro = 'todos';
        $this->resetPage();
        $this->cerrar();
    }
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

    /**
     * Vista operativa consolidada: solo lectura de cargos confirmados y pagos existentes.
     * No genera mensualidades, registra pagos ni activa cortes desde render().
     */
    public function render()
    {
        $hoy = today('America/Lima')->toDateString();
        $mesSeleccionado = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $this->mes)
            ? $this->mes : today('America/Lima')->format('Y-m');
        $inicioMes = $mesSeleccionado . '-01';
        $finMes = date('Y-m-t', strtotime($inicioMes));

        // Evitar multiplicar cargos cuando un cargo tiene más de una aplicación.
        $aplicaciones = DB::table('isp_aplicaciones_pago')
            ->select('cargo_id')
            ->selectRaw('SUM(monto) AS aplicado')
            ->groupBy('cargo_id');

        // Deuda = cargo confirmado menos aplicaciones existentes, con suelo de cero.
        // Solo la deuda de Internet ya vencida da lugar a una revisión de corte.
        $saldos = DB::table('isp_cargos as c')
            ->leftJoinSub($aplicaciones, 'ap', 'ap.cargo_id', '=', 'c.id')
            ->select('c.cliente_id')
            ->selectRaw('SUM(GREATEST(c.monto - COALESCE(ap.aplicado, 0), 0)) AS deuda_total')
            ->selectRaw("SUM(CASE WHEN c.categoria = 'internet' AND c.fecha_vencimiento < ? THEN GREATEST(c.monto - COALESCE(ap.aplicado, 0), 0) ELSE 0 END) AS internet_vencido", [$hoy])
            ->selectRaw('COUNT(*) AS cantidad_cargos')
            ->groupBy('c.cliente_id');

        // Valor de cargos emitidos del período elegido, no pagos en efectivo del mes.
        $cargosMes = DB::table('isp_mensualidades_generadas as mg')
            ->join('isp_cargos as cm', 'cm.id', '=', 'mg.cargo_id')
            ->where('mg.periodo', $inicioMes)
            ->select('mg.cliente_id')
            ->selectRaw('SUM(cm.monto) AS cargo_mes')
            ->groupBy('mg.cliente_id');

        $estadosServicio = DB::table('servicios')
            ->select('cliente_id')
            ->selectRaw("SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos")
            ->selectRaw("SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) AS suspendidos")
            ->selectRaw('COUNT(*) AS total_servicios')
            ->groupBy('cliente_id');

        $clientes = Cliente::query()
            ->leftJoinSub($saldos, 'deu', 'deu.cliente_id', '=', 'clientes.id')
            ->leftJoinSub($cargosMes, 'per', 'per.cliente_id', '=', 'clientes.id')
            ->leftJoinSub($estadosServicio, 'srv', 'srv.cliente_id', '=', 'clientes.id')
            ->leftJoin('isp_configuracion_mensual as cfg', 'cfg.cliente_id', '=', 'clientes.id')
            ->select('clientes.*', 'cfg.habilitado as facturacion_habilitada')
            ->selectRaw('COALESCE(deu.deuda_total, 0) AS deuda_total')
            ->selectRaw('COALESCE(deu.internet_vencido, 0) AS internet_vencido')
            ->selectRaw('COALESCE(deu.cantidad_cargos, 0) AS cantidad_cargos')
            ->selectRaw('COALESCE(per.cargo_mes, 0) AS cargo_mes')
            ->selectRaw('COALESCE(srv.activos, 0) AS servicios_activos')
            ->selectRaw('COALESCE(srv.suspendidos, 0) AS servicios_suspendidos')
            ->selectRaw('COALESCE(srv.total_servicios, 0) AS total_servicios')
            ->when(trim($this->buscar) !== '', function ($q) {
                $v = trim($this->buscar);
                $q->where(function ($w) use ($v) {
                    $w->where('clientes.codigo', 'like', '%'.$v.'%')
                        ->orWhere('clientes.nombre', 'like', '%'.$v.'%')
                        ->orWhere('clientes.telefono', 'like', '%'.$v.'%')
                        ->orWhere('clientes.direccion', 'like', '%'.$v.'%');
                });
            })
            ->when($this->filtroSede !== 'todas', fn ($q) => $q->where('clientes.sede', $this->filtroSede))
            ->when($this->filtroConexion === 'activo', fn ($q) => $q->where('srv.activos', '>', 0))
            ->when($this->filtroConexion === 'suspendido', fn ($q) => $q->whereRaw('COALESCE(srv.activos, 0) = 0 AND COALESCE(srv.suspendidos, 0) > 0'))
            ->when($this->filtroConexion === 'sin_servicio', fn ($q) => $q->whereNull('srv.total_servicios'))
            ->when($this->filtroFacturacion === 'habilitada', fn ($q) => $q->where('cfg.habilitado', 1))
            ->when($this->filtroFacturacion === 'deshabilitada', fn ($q) => $q->where(fn ($w) => $w->whereNull('cfg.habilitado')->orWhere('cfg.habilitado', 0)))
            ->when($this->filtroDeuda === 'con_deuda', fn ($q) => $q->where('deu.deuda_total', '>', 0))
            ->when($this->filtroDeuda === 'al_dia', fn ($q) => $q->whereRaw('COALESCE(deu.deuda_total, 0) = 0 AND COALESCE(deu.cantidad_cargos, 0) > 0'))
            ->when($this->filtroDeuda === 'sin_cargos', fn ($q) => $q->whereNull('deu.cantidad_cargos'))
            ->when($this->filtroDeuda === 'vencido', fn ($q) => $q->where('deu.internet_vencido', '>', 0))
            // Compatibilidad con el filtro anterior, si quedó seleccionado en una sesión.
            ->when($this->filtro === 'vencidos', fn ($q) => $q->where('deu.internet_vencido', '>', 0))
            ->when($this->filtro === 'pagos', fn ($q) => $q->whereIn('clientes.id', DB::table('isp_pagos')->select('cliente_id')))
            ->orderBy('clientes.nombre')->paginate(15);

        $ids = $clientes->getCollection()->pluck('id')->all();
        $ultimosPagos = empty($ids) ? collect() : DB::table('isp_pagos')
            ->whereIn('cliente_id', $ids)
            ->orderByDesc('fecha_pago')->orderByDesc('id')
            ->get(['cliente_id', 'monto', 'fecha_pago', 'categoria'])
            ->unique('cliente_id')->keyBy('cliente_id');

        $totalGlobal = DB::query()->fromSub($saldos, 'balance')
            ->selectRaw('COALESCE(SUM(balance.deuda_total), 0) AS pendiente_total')
            ->selectRaw('SUM(CASE WHEN balance.deuda_total > 0 THEN 1 ELSE 0 END) AS con_deuda')
            ->selectRaw('SUM(CASE WHEN balance.internet_vencido > 0 THEN 1 ELSE 0 END) AS por_revisar')
            ->first();

        return view('livewire.cobranza.gestion', [
            'clientes' => $clientes,
            'ultimosPagos' => $ultimosPagos,
            'mesSeleccionado' => $mesSeleccionado,
            'totalClientes' => Cliente::count(),
            'pendienteTotal' => (float) ($totalGlobal->pendiente_total ?? 0),
            'clientesConDeuda' => (int) ($totalGlobal->con_deuda ?? 0),
            'porRevisar' => (int) ($totalGlobal->por_revisar ?? 0),
            'cobradoMes' => (float) DB::table('isp_pagos')
                ->whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto'),
            'sedes' => DB::table('clientes')->whereNotNull('sede')
                ->where('sede', '<>', '')->distinct()->orderBy('sede')->pluck('sede'),
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
