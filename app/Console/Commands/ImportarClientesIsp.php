<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportarClientesIsp extends Command
{
    protected $signature = 'isp:importar-clientes
        {archivo : Ruta al archivo CSV dentro de tu proyecto}
        {--aplicar : Confirma que los clientes nuevos se guarden en MySQL}';

    protected $description = 'Importa clientes nuevos del Excel preparado; no modifica clientes existentes, servicios ni pagos.';

    public function handle(): int
    {
        $entrada = (string) $this->argument('archivo');
        $esAbsoluta = str_starts_with($entrada, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $entrada);
        $ruta = $esAbsoluta ? $entrada : base_path($entrada);

        if (!is_readable($ruta)) {
            $this->error("No puedo leer el archivo: {$ruta}");
            return self::FAILURE;
        }

        $csv = fopen($ruta, 'rb');
        if ($csv === false) {
            $this->error('No pude abrir el CSV.');
            return self::FAILURE;
        }

        try {
            $esperadas = ['codigo', 'nombre', 'documento', 'telefono', 'sector', 'direccion', 'observaciones'];
            $encabezados = fgetcsv($csv);
            if ($encabezados === false) {
                throw new RuntimeException('El CSV está vacío.');
            }

            $encabezados = array_map(
                fn ($valor) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $valor)),
                $encabezados
            );
            if ($encabezados !== $esperadas) {
                throw new RuntimeException('Las columnas del CSV no tienen el orden esperado. No se modificó MySQL.');
            }

            $filas = [];
            $vistos = [];
            $linea = 1;

            while (($valores = fgetcsv($csv)) !== false) {
                $linea++;
                if ($valores === [null] || $valores === []) {
                    continue;
                }
                if (count($valores) !== count($esperadas)) {
                    throw new RuntimeException("Cantidad de columnas incorrecta en línea {$linea}.");
                }
                $dato = array_combine($esperadas, array_map('trim', $valores));
                $codigo = strtoupper($dato['codigo']);
                $nombre = $dato['nombre'];
                if ($codigo === '' || $nombre === '') {
                    throw new RuntimeException("Código o nombre vacío en línea {$linea}.");
                }
                if (isset($vistos[$codigo])) {
                    throw new RuntimeException("Código repetido en CSV: {$codigo}.");
                }
                $vistos[$codigo] = true;
                $dato['codigo'] = $codigo;

                foreach (['codigo' => 30, 'nombre' => 160, 'documento' => 20, 'telefono' => 20, 'sector' => 120] as $campo => $limite) {
                    if (mb_strlen($dato[$campo]) > $limite) {
                        throw new RuntimeException("Campo {$campo} demasiado largo en línea {$linea}.");
                    }
                }
                foreach (['documento', 'telefono', 'sector', 'direccion', 'observaciones'] as $campo) {
                    if ($dato[$campo] === '') {
                        $dato[$campo] = null;
                    }
                }
                $filas[] = $dato;
            }
            if ($filas === []) {
                throw new RuntimeException('No se encontraron clientes con datos para importar.');
            }

            $existentes = Cliente::query()
                ->whereIn('codigo', array_column($filas, 'codigo'))
                ->pluck('codigo')
                ->map(fn ($codigo) => strtoupper($codigo))
                ->all();
            $codigosExistentes = array_fill_keys($existentes, true);
            $nuevos = array_values(array_filter(
                $filas,
                fn ($fila) => !isset($codigosExistentes[$fila['codigo']])
            ));

            $this->info('Clientes válidos en el CSV: ' . count($filas));
            $this->line('Ya existentes (se conservan sin modificar): ' . (count($filas) - count($nuevos)));
            $this->info('Clientes nuevos: ' . count($nuevos));

            if (!$this->option('aplicar')) {
                $this->warn('MODO SIMULACIÓN: no se guardó nada.');
                $this->line('Para insertar los nuevos clientes, repite el comando con --aplicar.');
                return self::SUCCESS;
            }

            DB::transaction(function () use ($nuevos): void {
                foreach ($nuevos as $dato) {
                    Cliente::create($dato);
                }
            });

            $this->info('Importación terminada. Clientes insertados: ' . count($nuevos));
            $this->warn('No se importaron saldos, mensualidades, servicios ni estados de corte. Deben verificarse por separado.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($csv);
        }
    }
}
