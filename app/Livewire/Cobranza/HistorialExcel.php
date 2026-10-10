<?php

namespace App\Livewire\Cobranza;

use App\Models\Cliente;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class HistorialExcel extends Component
{
    use WithPagination;

    public string $buscar = '';
    public string $mes = '2026-09';
    public string $filtro = 'todos';
    public ?int $clienteSeleccionado = null;

    public function updatedBuscar(): void
    {
        $this->resetPage();
        $this->clienteSeleccionado = null;
    }

    public function updatedMes(): void
    {
        $this->resetPage();
    }

    public function updatedFiltro(): void
    {
        $this->resetPage();
    }

    public function verCliente(int $clienteId): void
    {
        Cliente::findOrFail($clienteId);
        $this->clienteSeleccionado = $this->clienteSeleccionado === $clienteId ? null : $clienteId;
    }

    public function render()
    {
        $periodos = [];
        $fecha = CarbonImmutable::create(2026, 9, 1, 0, 0, 0, 'America/Lima');
        for ($i = 0; $i < 29; ++$i) {
            $periodos[] = [
                'valor' => $fecha->format('Y-m'),
                'nombre' => ucfirst($fecha->locale('es')->translatedFormat('F Y')),
            ];
            $fecha = $fecha->subMonth();
        }
        if (!in_array($this->mes, array_column($periodos, 'valor'), true)) {
            $this->mes = '2026-09';
        }
        $periodoSeleccionado = $this->mes . '-01';

        $clientes = Cliente::query()
            ->when(trim($this->buscar) !== '', function ($consulta) {
                $texto = trim($this->buscar);
                $consulta->where(function ($q) use ($texto) {
                    $q->where('codigo', 'like', "%{$texto}%")
                        ->orWhere('nombre', 'like', "%{$texto}%")
                        ->orWhere('telefono', 'like', "%{$texto}%");
                });
            })
            ->when($this->filtro === 'con_importe', fn ($q) => $q->whereIn('id',
                DB::table('isp_historial_excel')->where('periodo', $periodoSeleccionado)
                    ->where('importe_anotado', '>', 0)->select('cliente_id')))
            ->when($this->filtro === 'sin_importe', fn ($q) => $q->whereNotIn('id',
                DB::table('isp_historial_excel')->where('periodo', $periodoSeleccionado)
                    ->where('importe_anotado', '>', 0)->select('cliente_id')))
            ->orderBy('nombre')->paginate(12);

        $ids = $clientes->pluck('id')->all();
        $mesPorCliente = DB::table('isp_historial_excel')
            ->whereIn('cliente_id', $ids)->where('periodo', $periodoSeleccionado)
            ->get()->keyBy('cliente_id');
        $referencias = DB::table('isp_referencias_excel')->whereIn('cliente_id', $ids)
            ->get()->keyBy('cliente_id');
        $clienteDetalle = $this->clienteSeleccionado ? Cliente::find($this->clienteSeleccionado) : null;
        $historialDetalle = $clienteDetalle
            ? DB::table('isp_historial_excel')->where('cliente_id', $clienteDetalle->id)
                ->orderByDesc('periodo')->get()
            : collect();

        return view('livewire.cobranza.historial-excel', [
            'clientes' => $clientes,
            'mesPorCliente' => $mesPorCliente,
            'referencias' => $referencias,
            'periodos' => $periodos,
            'clienteDetalle' => $clienteDetalle,
            'historialDetalle' => $historialDetalle,
            'totalRegistros' => DB::table('isp_historial_excel')->count(),
            'registrosPositivos' => DB::table('isp_historial_excel')->where('importe_anotado', '>', 0)->count(),
            'conImporteMes' => DB::table('isp_historial_excel')->where('periodo', $periodoSeleccionado)
                ->where('importe_anotado', '>', 0)->count(),
        ]);
    }
}
