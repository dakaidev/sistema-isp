<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Reparación única y auditada: conserva el ingreso y su fecha, revierte una aplicación incorrecta. */
class ReconciliarWiliSeptiembre extends Command
{
    protected $signature = 'isp:reconciliar-wili-septiembre {--aplicar : Confirmar el cambio después de ver la simulación}';
    protected $description = 'Reasignar el pago 1 de CU229: julio S/50, septiembre S/46; agosto exonerado, octubre pendiente.';

    public function handle(): int
    {
        try {
            $info = DB::transaction(function () {
                $cliente = DB::table('clientes')->where('codigo', 'CU229')->lockForUpdate()->first();
                if (!$cliente) throw new RuntimeException('No existe CU229. No se ha cambiado nada.');

                $pago = DB::table('isp_pagos')->where('id', 1)->lockForUpdate()->first();
                if (!$pago || (int)$pago->cliente_id !== (int)$cliente->id ||
                    $pago->fecha_pago !== '2026-09-08' || $this->centavos($pago->monto) !== 9600 ||
                    $pago->categoria !== 'internet') {
                    throw new RuntimeException('El pago 1 no coincide exactamente con CU229/S/96/07-09-2026. No se cambió nada.');
                }

                $cargo = DB::table('isp_cargos')->where('id', 9)->lockForUpdate()->first();
                if (!$cargo || (int)$cargo->cliente_id !== (int)$cliente->id ||
                    $cargo->categoria !== 'internet' || $this->centavos($cargo->monto) !== 7000 ||
                    stripos($cargo->descripcion, 'octubre 2026') === false) {
                    throw new RuntimeException('El cargo 9 no corresponde a octubre 2026 por S/70. No se cambió nada.');
                }

                // Comprobación de idempotencia: no intentar repetir ni recomponer una operación parcialmente cambiada.
                if (DB::table('isp_correcciones_aplicaciones')->where('aplicacion_original_id', 1)->exists()) {
                    return 'YA_CORREGIDO';
                }
                $aplicacion = DB::table('isp_aplicaciones_pago')->where('id', 1)->lockForUpdate()->first();
                if (!$aplicacion || (int)$aplicacion->pago_id !== 1 ||
                    (int)$aplicacion->cargo_id !== 9 || $this->centavos($aplicacion->monto) !== 7000) {
                    throw new RuntimeException('La aplicación ID 1 ya no coincide con los S/70 a octubre. No se cambió nada.');
                }
                if (DB::table('isp_aplicaciones_pago')->where('pago_id', 1)->count() !== 1 ||
                    DB::table('isp_repartos_periodos')->where('pago_id', 1)->exists() ||
                    DB::table('isp_exoneraciones_periodos')->where('cliente_id', $cliente->id)
                        ->where('periodo', '2026-08-01')->exists()) {
                    throw new RuntimeException('Hay aplicaciones/repartos previos adicionales. Se detuvo para evitar duplicados.');
                }

                if (!$this->option('aplicar')) return 'SIMULACION';

                $ahora = now();
                DB::table('isp_correcciones_aplicaciones')->insert([
                    'aplicacion_original_id' => 1, 'pago_id' => 1, 'cargo_id' => 9,
                    'monto_original' => '70.00',
                    'motivo' => 'Aplicación automática equivocada de pago de septiembre a octubre; se conserva pago original.',
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
                DB::table('isp_aplicaciones_pago')->where('id', 1)->where('pago_id', 1)->where('cargo_id', 9)->delete();
                DB::table('isp_repartos_periodos')->insert([
                    ['cliente_id' => $cliente->id, 'pago_id' => 1, 'periodo' => '2026-07-01', 'monto' => '50.00',
                     'observacion' => 'Parte del ingreso recibido en septiembre 2026', 'created_at' => $ahora, 'updated_at' => $ahora],
                    ['cliente_id' => $cliente->id, 'pago_id' => 1, 'periodo' => '2026-09-01', 'monto' => '46.00',
                     'observacion' => 'Parte del ingreso recibido en septiembre 2026', 'created_at' => $ahora, 'updated_at' => $ahora],
                ]);
                DB::table('isp_exoneraciones_periodos')->insert([
                    'cliente_id' => $cliente->id, 'periodo' => '2026-08-01',
                    'motivo' => 'Exonerado por acuerdo: cliente indicó que no utilizó internet en agosto.',
                    'usuario_id' => null, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
                return 'APLICADO';
            }, 3);

            if ($info === 'YA_CORREGIDO') {
                $this->warn('La corrección de la aplicación 1 ya consta en auditoría. No se hizo ningún cambio adicional.');
                return self::SUCCESS;
            }
            $this->line('Cliente: CU229 (Wili Parari Quispe)');
            $this->line('Pago existente ID 1: S/ 96.00 recibido el 07/09/2026. NO se borrará ni duplicará.');
            $this->line('Aplicación equivocada ID 1: S/ 70.00 al cargo de octubre ID 9.');
            $this->line('Distribución correcta: julio S/ 50.00; septiembre S/ 46.00.');
            $this->line('Agosto 2026: exonerado por acuerdo, sin crear cargo retroactivo.');
            $this->line('Octubre: S/ 70.00 pendiente (si no existen otros pagos).');
            if ($info === 'SIMULACION') {
                $this->warn('SIMULACIÓN. No se guardó nada. Repite con --aplicar tras revisar.');
            } else {
                $this->info('APLICADO. Pago intacto; aplicación errónea archivada en auditoría; reparto guardado.');
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->warn('No se aplicó la operación; revisa el estado antes de reintentar.');
            return self::FAILURE;
        }
    }

    private function centavos(mixed $monto): int
    {
        return (int)round(((float)$monto) * 100);
    }
}
