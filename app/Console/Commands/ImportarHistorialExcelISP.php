<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportarHistorialExcelISP extends Command
{
    protected $signature = 'isp:importar-historial-excel {archivo} {--aplicar : Guardar anotaciones historicas, NO registrar pagos ni cargos oficiales}';
    protected $description = 'Carga los importes por mes del Excel antiguo como historial de consulta; no modifica saldos actuales.';

    public function handle(): int
    {
        // El archivo debe indicarse relativo a la raiz del proyecto Laravel (Windows compatible).
        $relativa = trim((string) $this->argument('archivo'));
        if ($relativa === '' || str_starts_with($relativa, '/') || preg_match('/^[a-zA-Z]:/', $relativa)) {
            $this->error('Indica la ruta relativa al proyecto, por ejemplo storage/app/importaciones/historial_anotaciones_excel_2024_2026.csv');
            return self::FAILURE;
        }
        $ruta = base_path($relativa);
        if (!is_file($ruta) || !is_readable($ruta)) {
            $this->error("No se puede leer el CSV: {$ruta}");
            return self::FAILURE;
        }

        $fp = fopen($ruta, 'rb');
        if ($fp === false) {
            $this->error('No se pudo abrir el archivo.');
            return self::FAILURE;
        }
        try {
            $columnas = ['codigo', 'nombre_excel', 'periodo', 'importe_anotado', 'celda_excel'];
            $cabecera = fgetcsv($fp);
            if (!is_array($cabecera)) {
                throw new RuntimeException('CSV vacío.');
            }
            $cabecera = array_map(
                static fn ($item) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $item)),
                $cabecera
            );
            if ($cabecera !== $columnas) {
                throw new RuntimeException('Cabecera CSV incorrecta; no se guardó nada.');
            }

            $filas = [];
            $pares = [];
            $codigos = [];
            $n = 1;
            $positivos = 0;
            $ceros = 0;
            while (($valores = fgetcsv($fp)) !== false) {
                ++$n;
                if ($valores === [null] || $valores === []) continue;
                if (count($valores) !== count($columnas)) {
                    throw new RuntimeException("Número incorrecto de columnas en fila {$n}.");
                }
                $r = array_combine($columnas, array_map('trim', $valores));
                $codigo = strtoupper($r['codigo']);
                if ($codigo === '' || mb_strlen($r['nombre_excel']) > 255 || !preg_match('/^[A-Z]{1,3}[0-9]{1,6}$/D', $r['celda_excel'])) {
                    throw new RuntimeException("Código, nombre o celda inválida en fila {$n}.");
                }
                $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $r['periodo']);
                if (!$fecha || $fecha->format('Y-m-d') !== $r['periodo'] || $fecha->format('d') !== '01'
                    || $r['periodo'] < '2024-05-01' || $r['periodo'] > '2026-09-01') {
                    throw new RuntimeException("Período inválido en fila {$n}.");
                }
                if (!preg_match('/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D', $r['importe_anotado'])) {
                    throw new RuntimeException("Importe inválido en fila {$n}.");
                }
                $importe = number_format((float) $r['importe_anotado'], 2, '.', '');
                if ($importe > 9999999999.99) throw new RuntimeException("Importe excesivo en fila {$n}.");
                $llave = $codigo . '|' . $r['periodo'];
                if (isset($pares[$llave])) throw new RuntimeException("Cliente y mes duplicados en el CSV, fila {$n}.");
                $pares[$llave] = true;
                $codigos[$codigo] = true;
                $positivos += (float) $importe > 0 ? 1 : 0;
                $ceros += (float) $importe == 0 ? 1 : 0;
                $filas[] = [
                    'codigo' => $codigo,
                    'periodo' => $r['periodo'],
                    'importe_anotado' => $importe,
                    'nombre_excel' => $r['nombre_excel'],
                    'celda_excel' => $r['celda_excel'],
                ];
            }
            if ($filas === []) throw new RuntimeException('CSV sin datos.');

            $clientes = Cliente::query()->whereIn('codigo', array_keys($codigos))->pluck('id', 'codigo');
            if ($clientes->count() !== count($codigos)) {
                $faltantes = array_diff(array_keys($codigos), $clientes->keys()->all());
                throw new RuntimeException('Hay códigos que no existen en MySQL: ' . implode(', ', array_slice($faltantes, 0, 8)));
            }
            $existentes = DB::table('isp_historial_excel')->whereIn('cliente_id', $clientes->values()->all())
                ->get(['cliente_id','periodo','importe_anotado','celda_excel'])
                ->keyBy(static fn ($r) => $r->cliente_id . '|' . $r->periodo);

            $nuevos = [];
            $sinCambios = 0;
            $conflictos = [];
            foreach ($filas as $f) {
                $id = (int) $clientes[$f['codigo']];
                $llave = $id . '|' . $f['periodo'];
                $actual = $existentes->get($llave);
                if ($actual) {
                    if (number_format((float) $actual->importe_anotado, 2, '.', '') !== $f['importe_anotado']
                        || $actual->celda_excel !== $f['celda_excel']) {
                        $conflictos[] = $f['codigo'] . ' (' . $f['periodo'] . ')';
                    } else {
                        ++$sinCambios;
                    }
                    continue;
                }
                $nuevos[] = [
                    'cliente_id' => $id, 'periodo' => $f['periodo'],
                    'importe_anotado' => $f['importe_anotado'],
                    'nombre_excel' => $f['nombre_excel'], 'celda_excel' => $f['celda_excel'],
                    'fuente' => 'clientesUpina (1).xlsx',
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            $this->info('Clientes con casillas anotadas: ' . count($codigos));
            $this->line('Casillas mensuales con importe positivo: ' . $positivos);
            $this->line('Casillas con cero explícito: ' . $ceros);
            $this->line('Registros totales para historial: ' . count($filas));
            $this->line('Nuevos registros: ' . count($nuevos));
            $this->line('Ya existentes sin cambios: ' . $sinCambios);
            if ($conflictos !== []) {
                $this->error('Hay ' . count($conflictos) . ' conflictos con el historial ya guardado: ' . implode(', ', array_slice($conflictos, 0, 6)));
                $this->error('No se guardó nada. Nunca sobrescribimos un pago histórico automáticamente.');
                return self::FAILURE;
            }
            if (!$this->option('aplicar')) {
                $this->warn('SIMULACIÓN: no se guardó nada.');
                return self::SUCCESS;
            }
            DB::transaction(function () use ($nuevos) {
                foreach (array_chunk($nuevos, 200) as $bloque) {
                    DB::table('isp_historial_excel')->insert($bloque);
                }
            });
            $this->info('Historial de Excel importado correctamente. NO se modificaron isp_pagos, isp_cargos, saldos ni mensualidades.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($fp);
        }
    }
}
