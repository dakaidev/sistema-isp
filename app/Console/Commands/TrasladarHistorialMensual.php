<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** El respaldo mensual no implica comprobante fechado ni nuevo ingreso. */
class TrasladarHistorialMensual extends Command
{
    protected $signature = 'isp:trasladar-historico {--eliminar-originales : Elimina las dos tablas antiguas SOLO tras cotejo y respaldo} {--respaldo= : Ruta al respaldo SQL reciente y completo}';
    protected $description = 'Copia/coteja los registros mensuales antiguos sin tocar isp_pagos ni los cargos';

    private const NUEVA = 'isp_registros_mensuales_historicos';
    private const VIEJA = 'isp_historial_excel';

    public function handle(): int
    {
        try {
            if (!Schema::hasTable(self::NUEVA)) {
                throw new RuntimeException('Falta la tabla nueva. Ejecuta SOLO la migracion indicada en el LEEME.');
            }
            foreach (['clientes','isp_pagos','isp_cargos','isp_aplicaciones_pago','isp_anticipos_internet'] as $t) {
                if (!Schema::hasTable($t)) throw new RuntimeException("Falta la tabla protegida {$t}. No se toca nada.");
            }
            if (Schema::hasTable(self::VIEJA)) {
                // Si la tabla nueva esta vacia, copia TODAS las filas (incluidos importes cero),
                // en una transaccion. Si ya hay filas, solo coteja: no sobrescribe.
                DB::transaction(function () {
                    $originales = DB::table(self::VIEJA)
                        ->orderBy('cliente_id')->orderBy('periodo')
                        ->lockForUpdate()->get(['cliente_id','periodo','importe_anotado']);
                    if ($originales->isEmpty()) {
                        throw new RuntimeException('Tabla original vacia; se detiene para investigacion.');
                    }
                    if (DB::table(self::NUEVA)->count() === 0) {
                        foreach ($originales->chunk(300) as $grupo) {
                            DB::table(self::NUEVA)->insert($grupo->map(fn ($r) => [
                                'cliente_id' => $r->cliente_id,
                                'periodo' => $r->periodo,
                                'importe' => $r->importe_anotado,
                            ])->all());
                        }
                    }
                }, 3);
                $this->cotejar();
            } else {
                // Las originales no existen: consulta solo la nueva, sin inventar origen.
                $cantidad = DB::table(self::NUEVA)->count();
                if ($cantidad === 0) throw new RuntimeException('No hay registros en ninguna tabla historica.');
                $this->info("OK: tabla historica nueva con {$cantidad} registros; originales ya ausentes.");
                if ($this->option('eliminar-originales')) {
                    $this->warn('No hay tablas originales para eliminar.');
                }
                return self::SUCCESS;
            }

            if (!$this->option('eliminar-originales')) {
                $this->info('OK: COPIA Y COTEJO TERMINADOS. No se ha borrado ninguna tabla.');
                $this->line('Siguiente: revisar fichas, respaldar MySQL actualizado y solo despues retirar originales.');
                return self::SUCCESS;
            }
            $this->validarRespaldo((string)$this->option('respaldo'));
            // Tras cotejar una segunda vez, y haber verificado respaldo externo reciente,
            // retirar exclusivamente las DOS tablas antiguas. Jamás isp_pagos o isp_cargos.
            $this->cotejar();
            Schema::dropIfExists('isp_referencias_excel');
            Schema::dropIfExists(self::VIEJA);
            $this->info('OK: borradas SOLAMENTE isp_referencias_excel e isp_historial_excel.');
            $this->info('OK: historial mensual nuevo intacto. Pagos, deudas y adelantos intactos.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('ERROR: '.$e->getMessage());
            $this->warn('No ejecutes migraciones adicionales. Conserva el respaldo y muestra este error.');
            return self::FAILURE;
        }
    }

    private function cotejar(): void
    {
        // Lee todas las filas ordenadas, incluyendo ceros. Comparacion exacta por clave e importe.
        $origen = DB::table(self::VIEJA)->orderBy('cliente_id')->orderBy('periodo')
            ->get(['cliente_id','periodo','importe_anotado']);
        $destino = DB::table(self::NUEVA)->orderBy('cliente_id')->orderBy('periodo')
            ->get(['cliente_id','periodo','importe']);
        if ($origen->count() !== $destino->count() || $origen->isEmpty()) {
            throw new RuntimeException('Diferencia de cantidad de filas: original '.$origen->count().', nuevo '.$destino->count());
        }
        $totalCents = 0;
        foreach ($origen as $i => $f) {
            $g = $destino[$i];
            if ((int)$f->cliente_id !== (int)$g->cliente_id ||
                (string)$f->periodo !== (string)$g->periodo ||
                (int)round((float)$f->importe_anotado * 100) !== (int)round((float)$g->importe * 100)) {
                throw new RuntimeException('Diferencia detectada en la fila '.($i + 1).'; originales conservados.');
            }
            $totalCents += (int)round((float)$f->importe_anotado * 100);
        }
        $this->info(sprintf('OK: cotejados exactamente %d registros, incluidos ceros. Total historico anotado S/ %s.',
            $origen->count(), number_format($totalCents / 100, 2, '.', ',')));
    }

    private function validarRespaldo(string $archivo): void
    {
        if ($archivo === '') throw new RuntimeException('Debes pasar --respaldo=RUTA_AL_SQL_NUEVO para eliminar originales.');
        $real = realpath($archivo);
        if (!$real || !is_file($real) || filesize($real) < 100000) {
            throw new RuntimeException('No se encuentra un respaldo SQL completo (>100 KB).');
        }
        if (time() - filemtime($real) > 4 * 3600) {
            throw new RuntimeException('El respaldo debe ser reciente (menos de 4 horas). Haz uno nuevo.');
        }
        $fp = fopen($real, 'rb');
        if (!$fp) throw new RuntimeException('No se pudo leer el respaldo.');
        $head = fread($fp, 3000);
        fseek($fp, max(0, filesize($real) - 2000));
        $end = stream_get_contents($fp);
        fclose($fp);
        if (!str_contains($head, 'MySQL dump') || !str_contains($end, 'Dump completed on')) {
            throw new RuntimeException('El respaldo no parece un mysqldump finalizado.');
        }
        $root = realpath(base_path());
        if ($root && str_starts_with(strtolower(str_replace('\\', '/', $real)),
            strtolower(str_replace('\\', '/', $root)).'/')) {
            throw new RuntimeException('Respaldo dentro del proyecto: muevelo fuera de Git antes de continuar.');
        }
        $this->info('OK: respaldo SQL reciente, completo y fuera del proyecto verificado.');
    }
}
