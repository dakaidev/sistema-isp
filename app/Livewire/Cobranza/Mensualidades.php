<?php

namespace App\Livewire\Cobranza;

use App\Models\Cliente;
use App\Services\Cobranza\GeneradorMensualidades;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Mensualidades extends Component
{
    use WithPagination;

    public string $buscar = '';
    public string $filtro = 'todos';
    public ?int $clienteId = null;
    public string $clienteNombre = '';
    public string $tarifa = '';
    public string $inicioCobro = '';
    public bool $habilitado = false;
    public string $mes = '';

    // Vista de cargos del período y padrón individual; sin cambios de facturación automáticos.
    public string $vista = 'periodo';
    public string $filtroSede = 'todas';
    public string $filtroConexion = 'todos';
    public string $filtroFacturacion = 'todos';
    public string $filtroDeuda = 'pendientes';

    public function mount(): void
    {
        $this->mes = today('America/Lima')->format('Y-m');
        $this->inicioCobro = today('America/Lima')->startOfMonth()->toDateString();
    }

    public function updatedBuscar(): void { $this->resetPage('periodo'); $this->resetPage('padron'); }
    public function updatedFiltro(): void { $this->resetPage('padron'); }
    public function updatedMes(): void { $this->resetPage('periodo'); }
    public function updatedFiltroSede(): void { $this->resetPage('periodo'); }
    public function updatedFiltroConexion(): void { $this->resetPage('periodo'); }
    public function updatedFiltroFacturacion(): void { $this->resetPage('periodo'); }
    public function updatedFiltroDeuda(): void { $this->resetPage('periodo'); }

    public function cambiarVista(string $vista): void
    {
        if (!in_array($vista, ['periodo', 'padron'], true)) return;
        $this->vista = $vista;
    }

    public function limpiarFiltrosPeriodo(): void
    {
        $this->buscar = '';
        $this->filtroSede = 'todas';
        $this->filtroConexion = 'todos';
        $this->filtroFacturacion = 'todos';
        $this->filtroDeuda = 'pendientes';
        $this->resetPage('periodo');
    }

    public function editar(int $id): void
    {
        $this->vista = 'padron';
        $cliente = Cliente::findOrFail($id);
        $cfg = DB::table('isp_configuracion_mensual')->where('cliente_id', $id)->first();
        $this->clienteId = $id;
        $this->clienteNombre = $cliente->codigo . ' — ' . $cliente->nombre;
        $this->tarifa = $cfg && $cfg->tarifa_mensual !== null ? (string) $cfg->tarifa_mensual : '';
        $this->inicioCobro = $cfg?->inicio_cobro ?: today('America/Lima')->startOfMonth()->toDateString();
        $this->habilitado = $cfg ? (bool) $cfg->habilitado : false;
        $this->resetValidation();
    }

    public function cerrar(): void
    {
        $this->clienteId = null;
        $this->clienteNombre = '';
        $this->tarifa = '';
        $this->habilitado = false;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        if ($this->clienteId === null) abort(400);
        $datos = $this->validate([
            'tarifa' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
            'inicioCobro' => ['required', 'date_format:Y-m-d'],
            'habilitado' => ['boolean'],
        ]);
        DB::transaction(function () use ($datos) {
            Cliente::whereKey($this->clienteId)->lockForUpdate()->firstOrFail();
            $cfg = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->clienteId)->first();
            $values = [
                'tarifa_mensual' => number_format((float) $datos['tarifa'], 2, '.', ''),
                'inicio_cobro' => $datos['inicioCobro'],
                'habilitado' => (bool) $datos['habilitado'],
                'updated_at' => now(),
            ];
            if ($cfg) DB::table('isp_configuracion_mensual')->where('cliente_id', $this->clienteId)->update($values);
            else DB::table('isp_configuracion_mensual')->insert(['cliente_id' => $this->clienteId, 'created_at' => now()] + $values);
        });
        $this->cerrar();
        session()->flash('mensaje', 'Mensualidad configurada. Los cargos ya emitidos NO cambian.');
    }

    public function simular(GeneradorMensualidades $generador): void
    {
        try {
            $r = $generador->ejecutar($this->mes, false);
            session()->flash('mensaje', "SIMULACIÓN {$r['mes']}: {$r['nuevos']} cargos nuevos (S/ {$r['total']}), {$r['existentes']} ya existentes. No se guardó nada.");
        } catch (\Throwable $e) { $this->addError('mes', $e->getMessage()); }
    }

    public function generar(GeneradorMensualidades $generador): void
    {
        try {
            $r = $generador->ejecutar($this->mes, true);
            session()->flash('mensaje', "PERÍODO {$r['mes']}: {$r['nuevos']} cargos creados (S/ {$r['total']}); {$r['existentes']} ya existían y no se duplicaron.");
        } catch (\Throwable $e) { $this->addError('mes', $e->getMessage()); }
    }

    public function render()
    {
        // No consultamos un período incorrecto aunque el usuario edite manualmente el mes.
        $mesSeleccionado = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $this->mes)
            ? $this->mes : today('America/Lima')->format('Y-m');
        $periodo = $mesSeleccionado.'-01';

        // Solo pagos aplicados A LOS CARGOS del período: nunca confundir con efectivo
        // recibido ese mes, crédito sin aplicar o registros históricos sin cargo.
        $aplicaciones = DB::table('isp_aplicaciones_pago')
            ->select('cargo_id')->selectRaw('SUM(monto) as total_aplicado')
            ->groupBy('cargo_id');
        $estadoServicios = DB::table('servicios')
            ->select('cliente_id')
            ->selectRaw('COUNT(*) as total_servicios')
            ->selectRaw("SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos")
            ->selectRaw("SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) as suspendidos")
            ->groupBy('cliente_id');

        $base = DB::table('isp_mensualidades_generadas as mg')
            ->join('isp_cargos as cargo', 'cargo.id', '=', 'mg.cargo_id')
            ->join('clientes as cli', 'cli.id', '=', 'mg.cliente_id')
            ->leftJoin('isp_configuracion_mensual as cfg', 'cfg.cliente_id', '=', 'cli.id')
            ->leftJoinSub($aplicaciones, 'ap', 'ap.cargo_id', '=', 'cargo.id')
            ->leftJoinSub($estadoServicios, 'srv', 'srv.cliente_id', '=', 'cli.id')
            ->where('mg.periodo', $periodo);

        $totales = (clone $base)->selectRaw('COUNT(*) as emitidos')
            ->selectRaw('COALESCE(SUM(cargo.monto), 0) as facturado')
            ->selectRaw('COALESCE(SUM(LEAST(cargo.monto, COALESCE(ap.total_aplicado, 0))), 0) as aplicado')
            ->selectRaw('COALESCE(SUM(GREATEST(cargo.monto - COALESCE(ap.total_aplicado, 0), 0)), 0) as pendiente')
            ->selectRaw('COALESCE(SUM(CASE WHEN cargo.monto > COALESCE(ap.total_aplicado, 0) THEN 1 ELSE 0 END), 0) as clientes_pendientes')
            ->first();

        $resumenPeriodo = [
            'emitidos' => (int) ($totales->emitidos ?? 0),
            'facturado' => (float) ($totales->facturado ?? 0),
            'aplicado' => (float) ($totales->aplicado ?? 0),
            'pendiente' => (float) ($totales->pendiente ?? 0),
            'clientesPendientes' => (int) ($totales->clientes_pendientes ?? 0),
        ];

        $cargosPeriodo = null;
        if ($this->vista === 'periodo') {
            $cargosPeriodo = (clone $base)
                ->when($this->filtroSede !== 'todas', fn ($q) => $q->where('cli.sede', $this->filtroSede))
                ->when($this->filtroConexion === 'activo', fn ($q) => $q->where('srv.activos', '>', 0))
                ->when($this->filtroConexion === 'suspendido', fn ($q) => $q->whereRaw('COALESCE(srv.activos, 0) = 0 AND COALESCE(srv.suspendidos, 0) > 0'))
                ->when($this->filtroConexion === 'sin_servicio', fn ($q) => $q->whereNull('srv.total_servicios'))
                ->when($this->filtroFacturacion === 'habilitada', fn ($q) => $q->where('cfg.habilitado', 1))
                ->when($this->filtroFacturacion === 'deshabilitada', fn ($q) => $q->where(fn ($w) => $w->whereNull('cfg.habilitado')->orWhere('cfg.habilitado', 0)))
                ->when($this->filtroDeuda === 'pendientes', fn ($q) => $q->whereRaw('cargo.monto > COALESCE(ap.total_aplicado, 0)'))
                ->when($this->filtroDeuda === 'pagados', fn ($q) => $q->whereRaw('cargo.monto <= COALESCE(ap.total_aplicado, 0)'))
                ->when(trim($this->buscar) !== '', function ($q) {
                    $palabra = trim($this->buscar);
                    $q->where(function ($w) use ($palabra) {
                        $w->where('cli.nombre', 'like', '%'.$palabra.'%')
                            ->orWhere('cli.codigo', 'like', '%'.$palabra.'%')
                            ->orWhere('cli.telefono', 'like', '%'.$palabra.'%');
                    });
                })
                ->select([
                    'mg.id as mensualidad_id', 'cli.id as cliente_id', 'cli.codigo',
                    'cli.nombre', 'cli.telefono', 'cli.sede', 'cargo.monto as importe',
                    'cargo.fecha_vencimiento', 'cfg.habilitado', 'srv.total_servicios',
                    'srv.activos', 'srv.suspendidos',
                ])
                ->selectRaw('GREATEST(cargo.monto - COALESCE(ap.total_aplicado, 0), 0) as saldo')
                ->orderBy('cli.nombre')->paginate(12, ['*'], 'periodo');
        }

        // Padrón manual de configuración original: conservar todas las funciones.
        $clientes = null;
        if ($this->vista === 'padron') {
            $clientes = Cliente::query()
                ->leftJoin('isp_configuracion_mensual as cfg', 'cfg.cliente_id', '=', 'clientes.id')
                ->select(['clientes.id', 'clientes.codigo', 'clientes.nombre', 'clientes.telefono',
                    'cfg.tarifa_mensual', 'cfg.inicio_cobro', 'cfg.habilitado'])
                ->when($this->filtro === 'habilitados', fn ($q) => $q->where('cfg.habilitado', 1))
                ->when($this->filtro === 'sin_configurar', fn ($q) => $q->where(function ($s) {
                    $s->whereNull('cfg.habilitado')->orWhere('cfg.habilitado', 0);
                }))
                ->when(trim($this->buscar) !== '', function ($q) {
                    $v = trim($this->buscar);
                    $q->where(function ($s) use ($v) {
                        $s->where('clientes.nombre', 'like', '%'.$v.'%')
                            ->orWhere('clientes.codigo', 'like', '%'.$v.'%');
                    });
                })
                ->orderBy('clientes.nombre')->paginate(12, ['*'], 'padron');
        }

        return view('livewire.cobranza.mensualidades', [
            'clientes' => $clientes,
            'cargosPeriodo' => $cargosPeriodo,
            'resumenPeriodo' => $resumenPeriodo,
            'mesSeleccionado' => $mesSeleccionado,
            'sedes' => DB::table('clientes')->whereNotNull('sede')->where('sede', '<>', '')
                ->distinct()->orderBy('sede')->pluck('sede'),
            'totalClientes' => Cliente::count(),
            'totalHabilitados' => DB::table('isp_configuracion_mensual')->where('habilitado', true)->count(),
            'totalConfiguraciones' => DB::table('isp_configuracion_mensual')->count(),
        ]);
    }
}
