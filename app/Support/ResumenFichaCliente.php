<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Resumen exclusivamente de lectura. No genera cargos, pagos ni aplicaciones. */
final class ResumenFichaCliente
{
    public static function obtener(int $clienteId): array
    {
        $configuracion = DB::table('isp_configuracion_mensual')
            ->where('cliente_id', $clienteId)->first();

        $cargos = DB::table('isp_cargos as c')
            ->leftJoin('isp_mensualidades_generadas as m', function ($join) {
                $join->on('m.cargo_id', '=', 'c.id')
                    ->on('m.cliente_id', '=', 'c.cliente_id');
            })
            ->where('c.cliente_id', $clienteId)
            ->get([
                'c.id', 'c.categoria', 'c.monto', 'c.fecha_vencimiento',
                'c.descripcion', 'm.periodo',
            ]);

        $aplicadoPorCargo = $cargos->isEmpty()
            ? collect()
            : DB::table('isp_aplicaciones_pago')
                ->whereIn('cargo_id', $cargos->pluck('id'))
                ->select('cargo_id')
                ->selectRaw('SUM(monto) AS total_aplicado')
                ->groupBy('cargo_id')
                ->pluck('total_aplicado', 'cargo_id');

        $saldos = ['total' => 0, 'internet' => 0, 'equipo' => 0, 'otros' => 0, 'vencido_internet' => 0];
        $meses = [];
        $cargosSinMes = 0;
        $hoy = today('America/Lima')->toDateString();

        foreach ($cargos as $cargo) {
            // Trabajamos con centavos para no acumular errores de coma flotante.
            $emitido = (int) round((float) $cargo->monto * 100);
            $aplicado = (int) round((float) ($aplicadoPorCargo[$cargo->id] ?? 0) * 100);
            $pendiente = max(0, $emitido - $aplicado);
            if ($pendiente === 0) {
                continue;
            }

            $categoria = in_array($cargo->categoria, ['internet', 'equipo'], true)
                ? $cargo->categoria : 'otros';
            $saldos['total'] += $pendiente;
            $saldos[$categoria] += $pendiente;
            if ($categoria === 'internet' && (string) $cargo->fecha_vencimiento < $hoy) {
                $saldos['vencido_internet'] += $pendiente;
            }

            if ($categoria === 'internet' && $cargo->periodo) {
                $mes = substr((string) $cargo->periodo, 0, 7);
                $meses[$mes] = ($meses[$mes] ?? 0) + $pendiente;
            } elseif ($categoria === 'internet') {
                // Cargos manuales confirmados, sin periodo de mensualidad generada.
                $cargosSinMes += $pendiente;
            }
        }

        krsort($meses);
        $ultimoPago = DB::table('isp_pagos')
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_pago')->orderByDesc('id')
            ->first(['fecha_pago', 'monto', 'categoria', 'medio']);

        return [
            'configuracion' => $configuracion,
            'saldos' => array_map(fn (int $centavos): float => $centavos / 100, $saldos),
            'meses_pendientes' => array_map(fn (int $centavos): float => $centavos / 100, $meses),
            'internet_sin_periodo' => $cargosSinMes / 100,
            'ultimo_pago' => $ultimoPago,
        ];
    }
}
