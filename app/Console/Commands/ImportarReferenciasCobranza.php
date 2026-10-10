<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportarReferenciasCobranza extends Command
{
    protected $signature = 'isp:importar-referencias {archivo} {--aplicar : Guardar solo las referencias del Excel, NO crear deudas ni pagos}';
    protected $description = 'Importa estados y saldos del Excel como REFERENCIAS por revisar, nunca como deuda confirmada.';

    public function handle(): int
    {
        $entrada = (string) $this->argument('archivo');
        
        $ruta = base_path($entrada);

        if (!is_readable($ruta)) {
            $this->error("No se puede leer: {$ruta}");
            return self::FAILURE;
        }

        $archivo = fopen($ruta, 'rb');
        if ($archivo === false) {
            $this->error('No pude abrir el CSV.');
            return self::FAILURE;
        }

        try {
            $esperados = [
                'codigo', 'nombre', 'estado_en_excel', 'deuda_en_excel_sin_verificar',
                'nota_cobranza_excel', 'mensualidad_explicita_excel', 'tecnologia_en_excel',
            ];
            $cabecera = fgetcsv($archivo);
            if (!is_array($cabecera)) {
                throw new RuntimeException('CSV vacío.');
            }
            $cabecera = array_map(
                static fn ($v) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v)),
                $cabecera
            );
            if ($cabecera !== $esperados) {
                throw new RuntimeException('Cabecera del CSV incorrecta. No se guardó nada.');
            }

            $items = [];
            $codigos = [];
            $n = 1;
            while (($fila = fgetcsv($archivo)) !== false) {
                ++$n;
                if ($fila === [null] || $fila === []) {
                    continue;
                }
                if (count($fila) !== count($esperados)) {
                    throw new RuntimeException("Columnas inválidas en fila {$n}.");
                }
                $row = array_combine($esperados, array_map('trim', $fila));
                $codigo = strtoupper($row['codigo']);
                if ($codigo === '' || isset($codigos[$codigo])) {
                    throw new RuntimeException("Código vacío o duplicado en línea {$n}.");
                }
                $codigos[$codigo] = true;
                $deuda = $row['deuda_en_excel_sin_verificar'];
                if ($deuda !== '' && (!is_numeric($deuda) || (float) $deuda < 0 || (float) $deuda > 99999999)) {
                    throw new RuntimeException("Deuda no numérica o inválida en la línea {$n}.");
                }
                $items[] = [
                    'codigo' => $codigo,
                    'estado_excel' => mb_substr($row['estado_en_excel'], 0, 35) ?: null,
                    'deuda_excel' => $deuda === '' ? null : number_format((float) $deuda, 2, '.', ''),
                    'nota_excel' => $row['nota_cobranza_excel'] ?: null,
                    'mensualidad_excel' => mb_substr($row['mensualidad_explicita_excel'], 0, 150) ?: null,
                    'tecnologia_excel' => mb_substr($row['tecnologia_en_excel'], 0, 100) ?: null,
                ];
            }
            $clientes = Cliente::whereIn('codigo', array_column($items, 'codigo'))->pluck('id', 'codigo');
            if ($clientes->count() !== count($items)) {
                throw new RuntimeException('Algún código no existe en clientes. No se modificó MySQL.');
            }
            $existentes = DB::table('isp_referencias_excel')->whereIn('cliente_id', $clientes->values()->all())
                ->pluck('cliente_id')->flip();
            $nuevos = [];
            $positivos = 0;
            foreach ($items as $item) {
                $id = $clientes[$item['codigo']];
                if ($item['deuda_excel'] !== null && (float) $item['deuda_excel'] > 0) {
                    ++$positivos;
                }
                if (!$existentes->has($id)) {
                    unset($item['codigo']);
                    $item['cliente_id'] = $id;
                    $item['created_at'] = now();
                    $item['updated_at'] = now();
                    $nuevos[] = $item;
                }
            }
            $this->info('Clientes revisados en el CSV: '.count($items));
            $this->line('Referencias nuevas: '.count($nuevos));
            $this->warn("Saldos positivos en el Excel (NO confirmados): {$positivos}");
            if (!$this->option('aplicar')) {
                $this->warn('SIMULACIÓN: no se guardó nada.');
                return self::SUCCESS;
            }
            DB::transaction(function () use ($nuevos) {
                foreach (array_chunk($nuevos, 100) as $chunk) {
                    DB::table('isp_referencias_excel')->insertOrIgnore($chunk);
                }
            });
            $this->info('Referencias importadas. NO se crearon cargos, pagos, deudas ni cortes.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($archivo);
        }
    }
}
