<?php

namespace App\Livewire\Finanzas;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Resumen extends Component
{
    use WithPagination;

    public string $anio = '2026';
    public string $mes = '10';
    public bool $mostrarFormulario = false;

    public string $fechaGasto = '';
    public string $importe = '';
    public string $categoria = 'internet_mayorista';
    public string $descripcion = '';
    public string $medio = 'efectivo';
    public string $referencia = '';
    public string $observaciones = '';

    public ?int $gastoAnularId = null;
    public string $motivoAnulacion = '';

    private const CATEGORIAS = [
        'internet_mayorista' => 'Proveedor de internet',
        'energia' => 'Electricidad',
        'personal' => 'Personal y técnicos',
        'transporte' => 'Transporte',
        'equipos' => 'Compra de equipos',
        'alquiler' => 'Alquiler',
        'mantenimiento' => 'Mantenimiento',
        'otros' => 'Otros gastos',
    ];

    private const MEDIOS = [
        'efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin',
        'transferencia' => 'Transferencia', 'otro' => 'Otro',
    ];

    public function mount(): void
    {
        $hoy = CarbonImmutable::now('America/Lima');
        $this->anio = $hoy->format('Y');
        $this->mes = $hoy->format('m');
        $this->fechaGasto = $hoy->toDateString();
    }

    public function updatedAnio(): void { $this->resetPage(); }
    public function updatedMes(): void { $this->resetPage(); }

    public function nuevoGasto(): void
    {
        $this->gastoAnularId = null;
        $this->resetValidation();
        $this->fechaGasto = CarbonImmutable::now('America/Lima')->toDateString();
        $this->importe = '';
        $this->categoria = 'internet_mayorista';
        $this->descripcion = '';
        $this->medio = 'efectivo';
        $this->referencia = '';
        $this->observaciones = '';
        $this->mostrarFormulario = true;
    }

    public function cancelar(): void
    {
        $this->mostrarFormulario = false;
        $this->gastoAnularId = null;
        $this->motivoAnulacion = '';
        $this->resetValidation();
    }

    public function guardarGasto(): void
    {
        if (!$this->mostrarFormulario) abort(400);
        $datos = $this->validate([
            'fechaGasto' => ['required','date_format:Y-m-d', 'after_or_equal:2020-01-01',
                'before_or_equal:' . CarbonImmutable::now('America/Lima')->toDateString()],
            'importe' => ['required','numeric','decimal:0,2','between:0.01,9999999999.99'],
            'categoria' => ['required','in:' . implode(',', array_keys(self::CATEGORIAS))],
            'descripcion' => ['required','string','min:3','max:255'],
            'medio' => ['required','in:' . implode(',', array_keys(self::MEDIOS))],
            'referencia' => ['nullable','string','max:100'],
            'observaciones' => ['nullable','string','max:1000'],
        ]);

        DB::table('isp_gastos')->insert([
            'fecha_gasto' => $datos['fechaGasto'],
            'monto' => number_format((float) $datos['importe'], 2, '.', ''),
            'categoria' => $datos['categoria'],
            'descripcion' => trim($datos['descripcion']),
            'medio' => $datos['medio'],
            'referencia' => trim($datos['referencia'] ?? '') ?: null,
            'observaciones' => trim($datos['observaciones'] ?? '') ?: null,
            'usuario_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->anio = substr($datos['fechaGasto'],0,4);
        $this->mes = substr($datos['fechaGasto'],5,2);
        $this->resetPage();
        $this->cancelar();
        session()->flash('finanzas_mensaje', 'Gasto guardado en el mes de su fecha.');
    }

    public function solicitarAnulacion(int $id): void
    {
        $existe = DB::table('isp_gastos')->where('id', $id)->whereNull('anulado_at')->exists();
        if (!$existe) {
            $this->addError('motivoAnulacion', 'El gasto ya fue anulado o no existe.');
            return;
        }
        $this->cancelar();
        $this->gastoAnularId = $id;
    }

    public function confirmarAnulacion(): void
    {
        if ($this->gastoAnularId === null) abort(400);
        $this->validate(['motivoAnulacion' => ['required','string','min:5','max:255']]);
        $cambiados = DB::table('isp_gastos')
            ->where('id', $this->gastoAnularId)->whereNull('anulado_at')
            ->update([
                'anulado_at' => now(),
                'anulado_por' => Auth::id(),
                'motivo_anulacion' => trim($this->motivoAnulacion),
                'updated_at' => now(),
            ]);
        if ($cambiados !== 1) {
            throw ValidationException::withMessages(['motivoAnulacion' => 'El gasto ya estaba anulado. Actualiza la página.']);
        }
        $this->gastoAnularId = null;
        $this->motivoAnulacion = '';
        session()->flash('finanzas_mensaje', 'Gasto anulado. Se conserva para auditoría y no cuenta en los totales.');
    }

    public function render()
    {
        $anio = filter_var($this->anio, FILTER_VALIDATE_INT);
        $hoy = CarbonImmutable::now('America/Lima');
        $anio = $anio && $anio >= 2024 && $anio <= (int)$hoy->year ? $anio : (int)$hoy->year;
        $mes = filter_var($this->mes, FILTER_VALIDATE_INT);
        $mes = $mes && $mes >= 1 && $mes <= 12 ? $mes : (int)$hoy->month;
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = CarbonImmutable::parse($desde, 'America/Lima')->endOfMonth()->toDateString();
        $desdeAnio = sprintf('%04d-01-01', $anio);
        $hastaAnio = sprintf('%04d-12-31', $anio);

        // Cobros nuevos: usar la fecha real de pago (NO cargos ni repartos entre periodos).
        $pagosAnio = DB::table('isp_pagos')
            ->whereBetween('fecha_pago', [$desdeAnio, $hastaAnio])
            ->get(['fecha_pago','monto']);
        $gastosAnio = DB::table('isp_gastos')->whereNull('anulado_at')
            ->whereBetween('fecha_gasto', [$desdeAnio, $hastaAnio])
            ->get(['fecha_gasto','monto']);
        // Cobros históricos importados una sola vez: la columna ya identifica el mes de caja.
        // No crear pagos adicionales en isp_pagos: contar cada fuente una sola vez.
        $excelAnio = DB::table('isp_historial_excel')
            ->whereBetween('periodo', [$desdeAnio, $hastaAnio])
            ->get(['periodo','importe_anotado']);

        $sumasPago = array_fill(1,12,0);
        foreach ($pagosAnio as $fila) {
            $sumasPago[(int)substr($fila->fecha_pago,5,2)] += (int)round((float)$fila->monto * 100);
        }
        $sumasGasto = array_fill(1,12,0);
        foreach ($gastosAnio as $fila) {
            $sumasGasto[(int)substr($fila->fecha_gasto,5,2)] += (int)round((float)$fila->monto * 100);
        }
        $sumasExcel = array_fill(1,12,0);
        foreach ($excelAnio as $fila) {
            $sumasExcel[(int)substr($fila->periodo,5,2)] += (int)round((float)$fila->importe_anotado * 100);
        }

        // Ingresos acumulados = anotaciones anteriores importadas + pagos posteriores en MySQL.
        // En septiembre 2026, CU229 y CU174 figuraban con cero en el histórico,
        // por lo que sus pagos posteriores no estaban incluidos en las anotaciones anteriores.
        $sumasIngreso = array_fill(1, 12, 0);
        for ($n = 1; $n <= 12; $n++) {
            $sumasIngreso[$n] = $sumasExcel[$n] + $sumasPago[$n];
        }

        $meses = [];
        for ($n = 1; $n <= 12; $n++) {
            $meses[] = [
                'numero' => sprintf('%02d', $n),
                'nombre' => ucfirst(CarbonImmutable::create($anio, $n, 1, 0, 0, 0, 'America/Lima')
                    ->locale('es')->translatedFormat('F')),
                'ingresos' => $sumasIngreso[$n] / 100,
                'anteriores' => $sumasExcel[$n] / 100,
                'nuevos' => $sumasPago[$n] / 100,
                'gastos' => $sumasGasto[$n] / 100,
                'resultado' => ($sumasIngreso[$n] - $sumasGasto[$n]) / 100,
            ];
        }

        $pagosMes = DB::table('isp_pagos as p')
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->whereBetween('p.fecha_pago',[$desde,$hasta])
            ->orderByDesc('p.fecha_pago')->orderByDesc('p.id')
            ->get(['p.id','p.fecha_pago','p.monto','p.medio','p.categoria','c.codigo','c.nombre']);
        // Listado del histórico importado. El día exacto no existía en el archivo original.
        $cobrosAnterioresMes = DB::table('isp_historial_excel as h')
            ->join('clientes as c', 'c.id', '=', 'h.cliente_id')
            ->whereBetween('h.periodo', [$desde, $hasta])
            ->where('h.importe_anotado', '>', 0)
            ->orderBy('c.nombre')
            ->get(['h.id', 'h.importe_anotado', 'h.periodo', 'c.codigo', 'c.nombre']);

        // Avisar de posibles coincidencias: ambas fuentes tienen importe positivo para
        // un mismo cliente/mes. No excluir silenciosamente ni dar por duplicado un abono.
        $posiblesCoincidencias = DB::table('isp_historial_excel as h')
            ->join('clientes as c', 'c.id', '=', 'h.cliente_id')
            ->whereBetween('h.periodo', [$desde, $hasta])
            ->where('h.importe_anotado', '>', 0)
            ->whereExists(function ($consulta) use ($desde, $hasta) {
                $consulta->selectRaw('1')
                    ->from('isp_pagos as p')
                    ->whereColumn('p.cliente_id', 'h.cliente_id')
                    ->whereBetween('p.fecha_pago', [$desde, $hasta]);
            })
            ->orderBy('c.nombre')
            ->pluck('c.nombre')
            ->all();

        $gastosMes = DB::table('isp_gastos')->whereBetween('fecha_gasto', [$desde,$hasta])
            ->orderByDesc('fecha_gasto')->orderByDesc('id')
            ->paginate(12);

        return view('livewire.finanzas.resumen', [
            'meses' => $meses,
            'anioSeleccionado' => $anio,
            'mesSeleccionado' => $mes,
            'ingresosMes' => $sumasIngreso[$mes] / 100,
            'cobrosAnterioresTotal' => $sumasExcel[$mes] / 100,
            'cobrosNuevosTotal' => $sumasPago[$mes] / 100,
            'gastosMesTotal' => $sumasGasto[$mes] / 100,
            'resultadoMes' => ($sumasIngreso[$mes] - $sumasGasto[$mes]) / 100,
            'ingresosAnio' => array_sum($sumasIngreso) / 100,
            'gastosAnio' => array_sum($sumasGasto) / 100,
            'pagosMes' => $pagosMes,
            'cobrosAnterioresMes' => $cobrosAnterioresMes,
            'posiblesCoincidencias' => $posiblesCoincidencias,
            'gastosMes' => $gastosMes,
            'categorias' => self::CATEGORIAS,
            'medios' => self::MEDIOS,
            'anios' => range((int)$hoy->year,2024),
        ]);
    }
}
