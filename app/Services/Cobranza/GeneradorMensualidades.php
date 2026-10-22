<?php

namespace App\Services\Cobranza;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Cargo por cliente y mes. Nunca facturar desde ingresos previos. */
class GeneradorMensualidades
{
    public function ejecutar(string $mes, bool $aplicar = false): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes)) {
            throw new InvalidArgumentException('Escribe el mes como AAAA-MM, por ejemplo 2026-10.');
        }

        $inicio = CarbonImmutable::parse($mes . '-01', 'America/Lima')->startOfMonth();
        $hoy = CarbonImmutable::now('America/Lima')->startOfDay();
        if ($inicio->greaterThan($hoy)) {
            throw new InvalidArgumentException('No se pueden emitir cargos de meses futuros.');
        }

        $resumen = ['mes' => $mes, 'habilitados' => 0, 'nuevos' => 0, 'existentes' => 0, 'sin_configurar' => 0, 'total' => '0.00'];
        $resumen['sin_configurar'] = DB::table('clientes')->whereNotIn('id', DB::table('isp_configuracion_mensual')
            ->where('habilitado', true)->whereNotNull('tarifa_mensual')->select('cliente_id'))->count();
        $totalCentavos = 0;
        DB::table('isp_configuracion_mensual')->where('habilitado', true)
            ->whereNotNull('tarifa_mensual')->where('tarifa_mensual', '>', 0)
            ->whereDate('inicio_cobro', '<=', $inicio->endOfMonth()->toDateString())
            ->chunkById(100, function ($planes) use ($inicio, $aplicar, &$resumen, &$totalCentavos) {
                foreach ($planes as $plan) {
                    ++$resumen['habilitados'];
                    // Simulación no inserta registros y es segura para repetir.
                    if (!$aplicar) {
                        $yaExiste = DB::table('isp_mensualidades_generadas')
                            ->where('cliente_id', $plan->cliente_id)->where('periodo', $inicio->toDateString())->exists();
                        if ($yaExiste) ++$resumen['existentes'];
                        else {
                            ++$resumen['nuevos'];
                            $totalCentavos += $this->montoProrrateado($plan, $inicio);
                        }
                        continue;
                    }
                    // Bloquear al cliente coordina esta escritura con los pagos de Livewire.
                    $resultado = DB::transaction(function () use ($plan, $inicio) {
                        DB::table('clientes')->where('id', $plan->cliente_id)->lockForUpdate()->first();
                        $actual = DB::table('isp_configuracion_mensual')
                            ->where('cliente_id', $plan->cliente_id)->lockForUpdate()->first();
                        if (!$actual || !$actual->habilitado || !$actual->tarifa_mensual ||
                            $actual->inicio_cobro > $inicio->endOfMonth()->toDateString()) return [false, 0];
                        if (DB::table('isp_mensualidades_generadas')->where('cliente_id', $plan->cliente_id)
                            ->where('periodo', $inicio->toDateString())->exists()) return [false, 0];

                        $centavos = $this->montoProrrateado($actual, $inicio);
                        if ($centavos <= 0) return [false, 0];
                        $alta = CarbonImmutable::parse($actual->inicio_cobro, 'America/Lima');
                        $vence = $inicio->addDays(4); // 5 del mes
                        if ($alta->betweenIncluded($inicio, $inicio->endOfMonth()) && $alta->greaterThan($vence)) {
                            $vence = $alta->addDays(4); // Alta posterior al 5: 5 días desde alta
                        }
                        $monto = $this->dinero($centavos);
                        $idCargo = DB::table('isp_cargos')->insertGetId([
                            'cliente_id' => $plan->cliente_id,
                            'servicio_id' => null, // MVP: un abono mensual por cliente
                            'tipo' => 'mensualidad_automatica',
                            'categoria' => 'internet',
                            'descripcion' => 'Internet ' . $inicio->locale('es')->translatedFormat('F Y') .
                                ($alta->betweenIncluded($inicio->addDay(), $inicio->endOfMonth()) ? ' (primer mes proporcional)' : ''),
                            'monto' => $monto,
                            'fecha_vencimiento' => $vence->toDateString(),
                            'usuario_id' => null,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        DB::table('isp_mensualidades_generadas')->insert([
                            'cliente_id' => $plan->cliente_id, 'cargo_id' => $idCargo,
                            'periodo' => $inicio->toDateString(),
                            'tarifa_base' => $actual->tarifa_mensual,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        // No consumir pagos antiguos cuando se crea un mes nuevo.
                        // Solo una asignacion explicita puede aplicar anticipos a futuros meses.
                        // Aplicar EXCLUSIVAMENTE dinero reservado para esta mensualidad.
                        app(AplicadorAnticiposInternet::class)->alGenerar((int) $plan->cliente_id, (int) $idCargo, $inicio->toDateString());
                        return [true, $centavos];
                    }, 3);
                    if ($resultado[0]) {
                        ++$resumen['nuevos'];
                        $totalCentavos += $resultado[1];
                    } else ++$resumen['existentes'];
                }
            });
        $resumen['total'] = $this->dinero($totalCentavos);
        return $resumen;
    }

    /** Mantener crédito a favor y pagos parciales del módulo Cobranza anterior. */
    private function aplicarCreditoInternet(int $clienteId): void
    {
        $cargos = DB::table('isp_cargos')->where('cliente_id', $clienteId)->where('categoria', 'internet')
            ->orderBy('fecha_vencimiento')->orderBy('id')->get(['id', 'monto']);
        $pagos = DB::table('isp_pagos')->where('cliente_id', $clienteId)->where('categoria', 'internet')
            ->orderBy('fecha_pago')->orderBy('id')->get(['id', 'monto']);
        $aplicadosCargo = DB::table('isp_aplicaciones_pago as ap')
            ->join('isp_cargos as c', 'c.id', '=', 'ap.cargo_id')
            ->where('c.cliente_id', $clienteId)->where('c.categoria', 'internet')
            ->select('ap.cargo_id')->selectRaw('SUM(ap.monto) as total')
            ->groupBy('ap.cargo_id')->pluck('total', 'ap.cargo_id');
        $aplicadosPago = DB::table('isp_aplicaciones_pago as ap')
            ->join('isp_pagos as p', 'p.id', '=', 'ap.pago_id')
            ->where('p.cliente_id', $clienteId)->where('p.categoria', 'internet')
            ->select('ap.pago_id')->selectRaw('SUM(ap.monto) as total')
            ->groupBy('ap.pago_id')->pluck('total', 'ap.pago_id');
        $restantes = [];
        foreach ($cargos as $cargo) {
            $restantes[$cargo->id] = max(0, $this->centavos($cargo->monto) - $this->centavos($aplicadosCargo[$cargo->id] ?? '0'));
        }
        foreach ($pagos as $pago) {
            $disponible = max(0, $this->centavos($pago->monto) - $this->centavos($aplicadosPago[$pago->id] ?? '0'));
            foreach ($restantes as $cargoId => $pendiente) {
                if ($disponible <= 0) break;
                if ($pendiente <= 0) continue;
                $valor = min($disponible, $pendiente);
                DB::table('isp_aplicaciones_pago')->insert([
                    'pago_id' => $pago->id, 'cargo_id' => $cargoId,
                    'monto' => $this->dinero($valor), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $restantes[$cargoId] -= $valor;
                $disponible -= $valor;
            }
        }
    }

    private function montoProrrateado(object $plan, CarbonImmutable $mes): int
    {
        $base = $this->centavos($plan->tarifa_mensual);
        $alta = CarbonImmutable::parse($plan->inicio_cobro, 'America/Lima');
        $primerDia = $alta->greaterThan($mes) ? $alta : $mes;
        $dias = $primerDia->diffInDays($mes->endOfMonth()->startOfDay()) + 1;
        $dias = max(0, (int) round($dias));
        return (int) floor(($base * $dias / $mes->daysInMonth) + 0.5);
    }

    private function centavos(string|int|float $importe): int
    {
        return (int) round(((float) $importe) * 100);
    }

    private function dinero(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
