<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportarMensualidadesISP extends Command
{
    protected $signature = 'isp:importar-mensualidades {archivo} {--aplicar : Guardar las tarifas verificadas del CSV}';
    protected $description = 'Configura mensualidades confirmadas sin emitir cargos ni asumir importes del Excel antiguo.';

    public function handle(): int
    {
        $entrada = (string) $this->argument('archivo');
        // Acepta rutas relativas o absolutas, sin expresiones regulares problemáticas en Windows.
        $ruta = is_file($entrada) ? $entrada : base_path($entrada);
        if (!is_readable($ruta)) {
            $this->error('No existe o no se puede leer: ' . $ruta);
            return self::FAILURE;
        }
        $f = fopen($ruta, 'rb');
        if (!$f) return self::FAILURE;
        try {
            $columnas = ['codigo', 'nombre', 'estado_excel', 'tarifa_sugerida_excel',
                'tarifa_confirmada', 'inicio_cobro', 'activar_cobro'];
            $cabecera = fgetcsv($f);
            $cabecera = is_array($cabecera) ? array_map(static fn ($v) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $v)), $cabecera) : [];
            if ($cabecera !== $columnas) throw new RuntimeException('Encabezado CSV incorrecto. No se modificó MySQL.');
            $filas = [];
            $codigos = [];
            $activados = 0;
            $linea = 1;
            while (($valores = fgetcsv($f)) !== false) {
                ++$linea;
                if ($valores === [null]) continue;
                if (count($valores) !== count($columnas)) throw new RuntimeException("Columnas inválidas en línea {$linea}.");
                $row = array_combine($columnas, array_map('trim', $valores));
                $codigo = strtoupper($row['codigo']);
                if ($codigo === '' || isset($codigos[$codigo])) throw new RuntimeException("Código repetido o vacío, línea {$linea}.");
                $codigos[$codigo] = true;
                $habilitado = strtoupper($row['activar_cobro']) === 'SI';
                if (!in_array(strtoupper($row['activar_cobro']), ['SI', 'NO'], true))
                    throw new RuntimeException("activar_cobro debe ser SI o NO en línea {$linea}.");
                $tarifa = str_replace(',', '.', $row['tarifa_confirmada']);
                if ($tarifa !== '' && (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $tarifa) || (float) $tarifa > 99999999.99))
                    throw new RuntimeException("Tarifa inválida en línea {$linea}.");
                if ($habilitado && ($tarifa === '' || (float) $tarifa <= 0))
                    throw new RuntimeException("Falta tarifa confirmada para {$codigo}, línea {$linea}.");
                $fecha = $row['inicio_cobro'];
                $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
                if (!$dt || $dt->format('Y-m-d') !== $fecha)
                    throw new RuntimeException("Fecha de inicio inválida en línea {$linea}.");
                $filas[] = compact('codigo', 'tarifa', 'fecha', 'habilitado');
                if ($habilitado) ++$activados;
            }
            if (count($filas) === 0) throw new RuntimeException('CSV sin filas.');
            $clientes = Cliente::whereIn('codigo', array_column($filas, 'codigo'))->pluck('id', 'codigo');
            if (count($filas) !== $clientes->count()) throw new RuntimeException('Faltan clientes o hay códigos desconocidos. No se guardó nada.');
            $this->info('Clientes encontrados: ' . count($filas));
            $this->line('Mensualidades HABILITADAS: ' . $activados);
            $this->line('Pendientes/deshabilitados: ' . (count($filas) - $activados));
            if (!$this->option('aplicar')) {
                $this->warn('SIMULACIÓN: no se guardó nada.');
                return self::SUCCESS;
            }
            DB::transaction(function () use ($filas, $clientes) {
                foreach ($filas as $r) {
                    $id = $clientes[$r['codigo']];
                    DB::table('clientes')->where('id', $id)->lockForUpdate()->first();
                    $datos = [
                        'tarifa_mensual' => $r['tarifa'] === '' ? null : $r['tarifa'],
                        'inicio_cobro' => $r['fecha'],
                        'habilitado' => $r['habilitado'],
                        'updated_at' => now(),
                    ];
                    if (DB::table('isp_configuracion_mensual')->where('cliente_id', $id)->exists()) {
                        DB::table('isp_configuracion_mensual')->where('cliente_id', $id)->update($datos);
                    } else {
                        DB::table('isp_configuracion_mensual')->insert(['cliente_id' => $id] + $datos + ['created_at' => now()]);
                    }
                }
            });
            $this->info('Configuraciones guardadas. NO se generaron cargos ni pagos.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($f);
        }
    }
}
