<?php

namespace App\Services\Cobranza;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Se invoca dentro de la misma transaccion que genera el cargo mensual. */
class AplicadorAnticiposInternet
{
    public function alGenerar(int $clienteId, int $cargoId, string $periodo): void
    {
        $cargo = DB::table('isp_cargos')->where('id', $cargoId)
            ->where('cliente_id', $clienteId)->where('categoria', 'internet')->lockForUpdate()->first();
        if (!$cargo) throw new RuntimeException('Cargo de internet inexistente para aplicar adelantos.');
        $ocupado = $this->centavos(DB::table('isp_aplicaciones_pago')->where('cargo_id', $cargoId)->sum('monto'));
        $pendiente = max(0, $this->centavos($cargo->monto) - $ocupado);
        if (!$pendiente) return;

        // Fecha real de pago y luego ID para consumir las reservas del mes en orden estable.
        $reservas = DB::table('isp_anticipos_internet as a')
            ->join('isp_pagos as p', 'p.id', '=', 'a.pago_id')
            ->where('a.cliente_id', $clienteId)->whereDate('a.periodo', $periodo)
            ->whereColumn('a.monto_aplicado', '<', 'a.monto')
            ->orderBy('p.fecha_pago')->orderBy('p.id')->orderBy('a.id')
            ->select('a.*')->get();
        foreach ($reservas as $reserva) {
            if ($pendiente <= 0) break;
            $reserva = DB::table('isp_anticipos_internet')->where('id', $reserva->id)->lockForUpdate()->first();
            $sinAplicar = $this->centavos($reserva->monto) - $this->centavos($reserva->monto_aplicado);
            if ($sinAplicar <= 0) continue;
            $pago = DB::table('isp_pagos')->where('id', $reserva->pago_id)->first();
            if (!$pago || $pago->cliente_id !== $clienteId || $pago->categoria !== 'internet') {
                throw new RuntimeException('Reserva con pago inválido; no se generó el cargo mensual.');
            }
            // Detectar si otra pantalla intentó gastar un importe ya reservado.
            $aplicado = $this->centavos(DB::table('isp_aplicaciones_pago')
                ->where('pago_id', $reserva->pago_id)->sum('monto'));
            $historico = $this->centavos(DB::table('isp_repartos_periodos')
                ->where('pago_id', $reserva->pago_id)->sum('monto'));
            $otrasReservas = DB::table('isp_anticipos_internet')->where('pago_id', $reserva->pago_id)
                ->where('id', '!=', $reserva->id)->get(['monto', 'monto_aplicado']);
            $reservadoOtros = 0;
            foreach ($otrasReservas as $otra) {
                $reservadoOtros += max(0, $this->centavos($otra->monto) - $this->centavos($otra->monto_aplicado));
            }
            $libre = $this->centavos($pago->monto) - $aplicado - $historico - $reservadoOtros;
            if ($libre < $sinAplicar) {
                throw new RuntimeException('Un adelanto de internet ya fue comprometido en otra operación. Revisa el pago #'.$pago->id);
            }
            $usar = min($sinAplicar, $pendiente);
            $yaEnCargo = DB::table('isp_aplicaciones_pago')
                ->where('pago_id', $pago->id)->where('cargo_id', $cargoId)->exists();
            if ($yaEnCargo) {
                throw new RuntimeException('El pago #'.$pago->id.' ya está aplicado a este cargo.');
            }
            DB::table('isp_aplicaciones_pago')->insert([
                'pago_id' => $pago->id, 'cargo_id' => $cargoId,
                'monto' => $this->dinero($usar), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('isp_anticipos_internet')->where('id', $reserva->id)->update([
                'cargo_id' => $cargoId,
                'monto_aplicado' => $this->dinero($this->centavos($reserva->monto_aplicado) + $usar),
                'updated_at' => now(),
            ]);
            $pendiente -= $usar;
        }
    }

    private function centavos(string|float|int $importe): int
    {
        return (int) round((float)$importe * 100);
    }

    private function dinero(int $valor): string
    {
        return number_format($valor / 100, 2, '.', '');
    }
}
