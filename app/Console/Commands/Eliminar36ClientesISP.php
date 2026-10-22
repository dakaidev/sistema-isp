<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Borrado definitivo estrictamente acotado a 36 codigos, sus descendientes
 * por claves foraneas y ninguna otra fila. No altera integridad referencial.
 * Un respaldo SQL completo previo es obligatorio.
 */
class Eliminar36ClientesISP extends Command
{
    protected $signature = 'isp:eliminar-36-clientes {--aplicar : Ejecutar borrado definitivo} {--respaldo= : Ruta absoluta a mysqldump reciente y completo} {--confirmar= : Debe ser ELIMINAR-36}';
    protected $description = 'Eliminar definitivamente los 36 clientes solicitados y sus dependencias, con protecciones.';

    private const CODIGOS = [
        'TEST001', 'CU113', 'CU118', 'CU119', 'CU121', 'CU126',
        'CU127', 'CU128', 'CU130', 'CU133', 'CU136', 'CU141',
        'CU147', 'CU148', 'CU150', 'CU152', 'CU158', 'CU159',
        'CU161', 'CU162', 'CU164', 'CU167', 'CU171', 'CU172',
        'CU173', 'CU175', 'CU176', 'CU182', 'CU184', 'CU187',
        'CU192', 'CU209', 'CU256', 'CU266', 'CU267', 'CU270',
    ];

    public function handle(): int
    {
        try {
            if (DB::connection()->getDriverName() !== 'mysql') {
                throw new RuntimeException('Este comando requiere la base MySQL del Sistema ISP.');
            }
            if (DB::connection()->getDatabaseName() !== 'sistema_isp') {
                throw new RuntimeException('Base inesperada: se esperaba exactamente sistema_isp.');
            }
            if (count(self::CODIGOS) !== 36 || count(array_unique(self::CODIGOS)) !== 36) {
                throw new RuntimeException('La lista interna no contiene exactamente 36 codigos unicos.');
            }

            $plan = $this->prepararPlan(false);
            $this->mostrarPlan($plan);

            if (!$this->option('aplicar')) {
                $this->warn('SOLO REVISION: no se borro ni modifico nada.');
                $this->line('Para ejecutar: --aplicar --confirmar=ELIMINAR-36 --respaldo="RUTA_DEL_SQL"');
                return self::SUCCESS;
            }
            if ($this->option('confirmar') !== 'ELIMINAR-36') {
                throw new RuntimeException('Falta --confirmar=ELIMINAR-36. No se ha borrado nada.');
            }
            $this->verificarRespaldo((string) $this->option('respaldo'));

            DB::transaction(function () use ($plan): void {
                // Bloquea los clientes objetivo antes de recalcular el plan.
                DB::table('clientes')
                    ->whereIn(DB::raw('UPPER(TRIM(codigo))'), self::CODIGOS)
                    ->orderBy('id')->lockForUpdate()->get(['id']);

                $actual = $this->prepararPlan(true);
                if ($actual['ids'] !== $plan['ids'] || $actual['puertos'] !== $plan['puertos']) {
                    throw new RuntimeException('Cambiaron registros desde la revision. Transaccion cancelada.');
                }

                // Los puertos NAP son infraestructura fisica compartida: NO se borran.
                // Solo se desvinculan de servicios borrados, y quedan sin verificar.
                if ($actual['puertos'] !== []) {
                    $n = DB::table('isp_nap_puertos')
                        ->whereIn('id', $actual['puertos'])
                        ->update([
                            'servicio_id' => null,
                            'estado' => 'sin_verificar',
                            'updated_at' => now(),
                        ]);
                    if ($n !== count($actual['puertos'])) {
                        throw new RuntimeException('No se pudo desvincular la totalidad de puertos NAP.');
                    }
                }

                foreach ($actual['orden'] as $tabla) {
                    $ids = $actual['ids'][$tabla] ?? [];
                    if ($ids === []) {
                        continue;
                    }
                    $n = DB::table($tabla)->whereIn('id', $ids)->delete();
                    if ($n !== count($ids)) {
                        throw new RuntimeException("Borrado incompleto en {$tabla}. Transaccion revertida.");
                    }
                }
                $quedan = DB::table('clientes')
                    ->whereIn(DB::raw('UPPER(TRIM(codigo))'), self::CODIGOS)->count();
                if ($quedan !== 0) {
                    throw new RuntimeException("Quedan {$quedan} clientes objetivo: transaccion revertida.");
                }
            }, 1);

            $this->info('OK: BORRADOS DEFINITIVAMENTE los 36 clientes solicitados y sus registros dependientes.');
            $this->info('OK: transaccion completada; no se desactivaron claves foraneas.');
            $this->info('OK: cajas NAP y puertos fisicos conservados; puertos ligados a servicios borrados quedaron sin verificar.');
            $this->warn('Los pagos e historicos borrados ya no sumaran en Finanzas. Conserva el respaldo SQL.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('DETENIDO: '.$e->getMessage());
            $this->warn('Si hubo un error dentro de la transaccion, MySQL revierte todos sus cambios.');
            return self::FAILURE;
        }
    }

    private function prepararPlan(bool $enTransaccion): array
    {
        $clientes = DB::table('clientes')
            ->whereIn(DB::raw('UPPER(TRIM(codigo))'), self::CODIGOS)
            ->orderBy('id')
            ->get(['id', 'codigo']);
        $existentes = $clientes->map(fn ($c) => strtoupper(trim($c->codigo)))->all();
        $faltan = array_values(array_diff(self::CODIGOS, $existentes));
        if ($clientes->count() !== 36 || $faltan !== []) {
            throw new RuntimeException('Se esperaban los 36 codigos exactos en clientes. Faltantes: '.implode(', ', $faltan));
        }
        $ids = ['clientes' => $this->ordenarIds($clientes->pluck('id')->all())];
        $fks = $this->foreignKeys();
        // Descendientes por FK reales, independientemente del numero de tablas.
        // Nunca se borran las cajas NAP ni los puertos fisicos.
        for ($vuelta = 0; $vuelta < 50; $vuelta++) {
            $cambio = false;
            foreach ($fks as $fk) {
                $padre = $fk['padre'];
                $hijo = $fk['hijo'];
                if (!isset($ids[$padre]) || $padre === $hijo || $ids[$padre] === []) {
                    continue;
                }
                if ($hijo === 'isp_nap_puertos') {
                    continue;
                }
                if (in_array($hijo, ['isp_cajas_nap', 'isp_nap_movimientos', 'users', 'isp_gastos'], true)) {
                    throw new RuntimeException("Dependencia inesperada con tabla protegida: {$hijo}");
                }
                if ($fk['columna_padre'] !== 'id' || !Schema::hasColumn($hijo, 'id')) {
                    throw new RuntimeException("FK incompatible: {$hijo}.{$fk['columna']}");
                }
                $encontrados = DB::table($hijo)
                    ->whereIn($fk['columna'], $ids[$padre])
                    ->pluck('id')->all();
                $nuevos = $this->ordenarIds(array_merge($ids[$hijo] ?? [], $encontrados));
                if ($nuevos !== ($ids[$hijo] ?? [])) {
                    $ids[$hijo] = $nuevos;
                    $cambio = true;
                }
            }
            if (!$cambio) {
                break;
            }
            if ($vuelta === 49) {
                throw new RuntimeException('Grafo de dependencias no convergente.');
            }
        }

        // No borrar relaciones que tambien apunten a cobros/servicios de terceros.
        // Si una dependencia sale fuera de estos 36 clientes se cancela todo.
        foreach ($fks as $fk) {
            $hijo = $fk['hijo'];
            $padre = $fk['padre'];
            if ($hijo === 'isp_nap_puertos' || empty($ids[$hijo])) {
                continue;
            }
            if (!in_array($padre, ['clientes', 'servicios', 'isp_cargos', 'isp_pagos'], true)) {
                continue;
            }
            if ($fk['columna_padre'] !== 'id' || $ids[$padre] === []) {
                $vinculos = DB::table($hijo)
                    ->whereIn('id', $ids[$hijo])
                    ->whereNotNull($fk['columna'])->count();
            } else {
                $vinculos = DB::table($hijo)
                    ->whereIn('id', $ids[$hijo])
                    ->whereNotNull($fk['columna'])
                    ->whereNotIn($fk['columna'], $ids[$padre])->count();
            }
            if ($vinculos > 0) {
                throw new RuntimeException("{$vinculos} referencias cruzadas con terceros en {$hijo}.{$fk['columna']}. No se borrara nada.");
            }
        }

        // Detectar referencias no protegidas por FK para evitar dejar huerfanos.
        $expected = ['cliente_id' => 'clientes', 'servicio_id' => 'servicios', 'pago_id' => 'isp_pagos', 'cargo_id' => 'isp_cargos'];
        $columnas = DB::select("SELECT TABLE_NAME AS tabla, COLUMN_NAME AS columna FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME IN ('cliente_id','servicio_id','pago_id','cargo_id')");
        foreach ($columnas as $col) {
            $tabla = $col->tabla;
            $campo = $col->columna;
            $padre = $expected[$campo];
            if (empty($ids[$padre]) || !Schema::hasColumn($tabla, 'id')) {
                continue;
            }
            $enlazado = false;
            foreach ($fks as $fk) {
                if ($fk['hijo'] === $tabla && $fk['columna'] === $campo && $fk['padre'] === $padre) {
                    $enlazado = true;
                    break;
                }
            }
            if (!$enlazado) {
                $n = DB::table($tabla)->whereIn($campo, $ids[$padre])->count();
                if ($n > 0) {
                    throw new RuntimeException("Existen {$n} referencias sin FK en {$tabla}.{$campo}; requiere revision, nada se borra.");
                }
            }
        }

        $puertos = [];
        if (Schema::hasTable('isp_nap_puertos') && !empty($ids['servicios'])) {
            $puertos = $this->ordenarIds(DB::table('isp_nap_puertos')
                ->whereIn('servicio_id', $ids['servicios'])->pluck('id')->all());
        }

        $orden = $this->ordenBorrado($ids, $fks);
        return ['ids' => $ids, 'puertos' => $puertos, 'orden' => $orden];
    }

    private function foreignKeys(): array
    {
        $rows = DB::select("SELECT TABLE_NAME AS hijo, COLUMN_NAME AS columna, REFERENCED_TABLE_NAME AS padre, REFERENCED_COLUMN_NAME AS columna_padre FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        return array_map(fn ($r) => (array) $r, $rows);
    }

    private function ordenBorrado(array $ids, array $fks): array
    {
        $aristas = [];
        foreach ($fks as $fk) {
            if ($fk['hijo'] === 'isp_nap_puertos') {
                continue;
            }
            if (!empty($ids[$fk['padre']]) && !empty($ids[$fk['hijo']])) {
                $aristas[$fk['padre']][$fk['hijo']] = true;
            }
        }
        $estado = [];
        $orden = [];
        $visitar = function (string $tabla) use (&$visitar, &$estado, &$orden, $aristas): void {
            if (($estado[$tabla] ?? 0) === 1) {
                throw new RuntimeException("Ciclo de FK en {$tabla}; no se borra nada.");
            }
            if (($estado[$tabla] ?? 0) === 2) {
                return;
            }
            $estado[$tabla] = 1;
            foreach (array_keys($aristas[$tabla] ?? []) as $hijo) {
                $visitar($hijo);
            }
            $estado[$tabla] = 2;
            $orden[] = $tabla; // hijos antes que padres
        };
        foreach (array_keys($ids) as $tabla) {
            $visitar($tabla);
        }
        return $orden;
    }

    private function ordenarIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private function mostrarPlan(array $plan): void
    {
        $this->line('Base: '.DB::connection()->getDatabaseName());
        $this->line('Clientes exactos: 36 (TEST001 y los 35 codigos CU indicados)');
        foreach ($plan['orden'] as $tabla) {
            $n = count($plan['ids'][$tabla] ?? []);
            if ($n > 0) {
                $this->line("  {$tabla}: {$n} filas para eliminar");
            }
        }
        $this->line('Puertos NAP a desvincular (sin borrar puertos): '.count($plan['puertos']));
        if (!empty($plan['ids']['isp_pagos'])) {
            $suma = DB::table('isp_pagos')->whereIn('id', $plan['ids']['isp_pagos'])->sum('monto');
            $this->warn('Se eliminaran pagos por S/ '.number_format((float) $suma, 2));
        }
        if (!empty($plan['ids']['isp_registros_mensuales_historicos'])) {
            $suma = DB::table('isp_registros_mensuales_historicos')
                ->whereIn('id', $plan['ids']['isp_registros_mensuales_historicos'])->sum('importe');
            $this->warn('Se eliminaran montos historicos por S/ '.number_format((float) $suma, 2));
        }
    }

    private function verificarRespaldo(string $ruta): void
    {
        $archivo = realpath($ruta);
        if (!$archivo || !is_file($archivo)) {
            throw new RuntimeException('Respaldo SQL inexistente.');
        }
        $rutaNormal = str_replace('\\', '/', $archivo);
        $proyecto = rtrim(str_replace('\\', '/', (string) realpath(base_path())), '/');
        if (str_starts_with(strtolower($rutaNormal), strtolower($proyecto.'/'))) {
            throw new RuntimeException('Respaldo dentro del proyecto: debe guardarse en Respaldos-ISP.');
        }
        if (stripos($rutaNormal, 'Respaldos-ISP') === false) {
            throw new RuntimeException('Respaldo no ubicado en Respaldos-ISP.');
        }
        if (filesize($archivo) < 100000 || filemtime($archivo) < time() - 4 * 3600) {
            throw new RuntimeException('Respaldo demasiado pequeno o antiguo.');
        }
        $f = fopen($archivo, 'rb');
        if ($f === false) {
            throw new RuntimeException('No se pudo leer el respaldo.');
        }
        $head = fread($f, 4096);
        fseek($f, -min(4096, filesize($archivo)), SEEK_END);
        $tail = stream_get_contents($f);
        fclose($f);
        if (!str_contains($head, 'MySQL dump') || !str_contains($tail, 'Dump completed on')) {
            throw new RuntimeException('El respaldo no tiene encabezado y cierre de mysqldump.');
        }
        $this->info('OK: respaldo SQL reciente y completo verificado: '.$archivo);
    }
}
