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

    public function mount(): void
    {
        $this->mes = today('America/Lima')->format('Y-m');
        $this->inicioCobro = today('America/Lima')->startOfMonth()->toDateString();
    }

    public function updatedBuscar(): void { $this->resetPage(); }
    public function updatedFiltro(): void { $this->resetPage(); }

    public function editar(int $id): void
    {
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
        $clientes = Cliente::query()
            ->leftJoin('isp_configuracion_mensual as cfg', 'cfg.cliente_id', '=', 'clientes.id')
            ->leftJoin('isp_referencias_excel as re', 're.cliente_id', '=', 'clientes.id')
            ->select(['clientes.id', 'clientes.codigo', 'clientes.nombre', 'clientes.telefono',
                'cfg.tarifa_mensual', 'cfg.inicio_cobro', 'cfg.habilitado', 're.estado_excel'])
            ->when($this->filtro === 'habilitados', fn ($q) => $q->where('cfg.habilitado', 1))
            ->when($this->filtro === 'sin_configurar', fn ($q) => $q->where(function ($s) {
                $s->whereNull('cfg.habilitado')->orWhere('cfg.habilitado', 0);
            }))
            ->when(trim($this->buscar) !== '', function ($q) {
                $v = trim($this->buscar);
                $q->where(function ($s) use ($v) {
                    $s->where('clientes.nombre', 'like', "%{$v}%")
                        ->orWhere('clientes.codigo', 'like', "%{$v}%");
                });
            })
            ->orderBy('clientes.nombre')->paginate(20);

        return view('livewire.cobranza.mensualidades', [
            'clientes' => $clientes,
            'totalHabilitados' => DB::table('isp_configuracion_mensual')->where('habilitado', true)->count(),
            'totalConfiguraciones' => DB::table('isp_configuracion_mensual')->count(),
        ]);
    }
}
