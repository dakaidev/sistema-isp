<?php

namespace App\Livewire\Finanzas;

use App\Support\IspAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Reporte de pagos ingresados (isp_pagos): una fila es un ingreso, incluso
 * cuando se distribuyó a varias mensualidades. No modifica ningún registro.
 * Los importes históricos sin pago fechado no se atribuyen a trabajadores.
 */
class CobrosPorUsuario extends Component
{
    use WithPagination;

    public string $anio = '';
    public string $mes = '';
    public string $usuarioSeleccionado = 'todos';

    public function boot(): void
    {
        IspAccess::enforce('finanzas.cobros');
    }

    public function mount(): void
    {
        $hoy = CarbonImmutable::now('America/Lima');
        $this->anio = $hoy->format('Y');
        $this->mes = $hoy->format('m');
    }

    public function updatedAnio(): void { $this->resetPage(); }
    public function updatedMes(): void { $this->resetPage(); }
    public function updatedUsuarioSeleccionado(): void { $this->resetPage(); }

    public function render()
    {
        // Siempre se vuelve a calcular desde la sesión. Un cobrador no puede
        // usar el estado público Livewire para consultar pagos ajenos.
        $admin = IspAccess::allows(Auth::user(), 'finanzas.view');
        $idActual = (int) Auth::id();
        $hoy = CarbonImmutable::now('America/Lima');

        $fechaPrimera = DB::table('isp_pagos')
            ->when(!$admin, fn ($q) => $q->where('usuario_id', $idActual))
            ->min('fecha_pago');
        $primerAnio = $fechaPrimera ? (int) substr((string) $fechaPrimera, 0, 4) : (int) $hoy->year;
        $primerAnio = max(2000, min($primerAnio, (int) $hoy->year));
        $anios = range((int) $hoy->year, $primerAnio);

        $anio = filter_var($this->anio, FILTER_VALIDATE_INT);
        $anio = $anio !== false && in_array($anio, $anios, true) ? $anio : (int) $hoy->year;
        $mes = filter_var($this->mes, FILTER_VALIDATE_INT);
        $mes = $mes !== false && $mes >= 1 && $mes <= 12 ? $mes : (int) $hoy->month;
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = CarbonImmutable::parse($desde, 'America/Lima')->endOfMonth()->toDateString();

        $consulta = DB::table('isp_pagos as p')
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.usuario_id')
            ->whereBetween('p.fecha_pago', [$desde, $hasta]);

        if (!$admin) {
            $consulta->where('p.usuario_id', $idActual);
        } elseif ($this->usuarioSeleccionado === 'sin_usuario') {
            $consulta->whereNull('p.usuario_id');
        } elseif (ctype_digit($this->usuarioSeleccionado)) {
            $consulta->where('p.usuario_id', (int) $this->usuarioSeleccionado);
        }

        // No usar isp_aplicaciones_pago ni repartos: multiplicarían un mismo
        // pago por la cantidad de mensualidades a las que fue aplicado.
        $total = (float) (clone $consulta)->sum('p.monto');
        $cantidad = (clone $consulta)->count('p.id');
        $porMedio = (clone $consulta)->select('p.medio')
            ->selectRaw('COUNT(p.id) as cantidad, SUM(p.monto) as total')
            ->groupBy('p.medio')->orderBy('p.medio')->get();

        $porUsuario = $admin ? (clone $consulta)
            ->select('p.usuario_id', 'u.name')
            ->selectRaw('COUNT(p.id) as cantidad, SUM(p.monto) as total')
            ->groupBy('p.usuario_id', 'u.name')
            ->orderByDesc('total')->get() : collect();

        $pagos = (clone $consulta)
            ->orderByDesc('p.fecha_pago')->orderByDesc('p.id')
            ->select(['p.id','p.fecha_pago','p.created_at','p.monto','p.medio',
                'p.categoria','c.codigo','c.nombre','u.name as registrado_por'])
            ->paginate(15);

        return view('livewire.finanzas.cobros-por-usuario', [
            'esAdmin' => $admin,
            'anios' => $anios,
            'anioSeleccionado' => $anio,
            'mesSeleccionado' => $mes,
            'nombreMes' => CarbonImmutable::create($anio, $mes, 1, 0, 0, 0, 'America/Lima')
                ->locale('es')->translatedFormat('F Y'),
            'usuarios' => $admin ? DB::table('users')->select(['id','name'])
                ->orderBy('name')->get() : collect(),
            'totalPagos' => $total,
            'cantidadPagos' => $cantidad,
            'porMedio' => $porMedio,
            'porUsuario' => $porUsuario,
            'pagos' => $pagos,
        ]);
    }
}
