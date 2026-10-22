<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Recuperación protegida de ubicaciones del Excel histórico.
 * SIN crear nuevas tablas y SIN cambiar sedes, clientes ni facturación.
 */
class RecuperarUbicacionesExcel extends Command
{
    protected $signature = 'isp:recuperar-gps-excel
        {--archivo= : CSV de 64 filas generado del Excel original}
        {--aplicar : Importar SOLO clientes con código y nombre coincidentes y GPS vacío}
        {--respaldo= : Ruta del mysqldump reciente (obligatoria al aplicar)}
        {--esperados= : Número exacto de registros que autoriza importar (obligatorio al aplicar)}';

    protected $description = 'Audita ubicaciones del Excel; importa sólo coincidencias inequívocas, con respaldo y confirmación.';

    private const CSV_SHA256 = '794888c388d99ce8718dcbb98aa4870bcbc691d219d0ec91a585fcba18ef38b1';
    private const TOTAL_EXCEL = 64;

    public function handle(): int
    {
        try {
            $ruta = $this->option('archivo');
            if (!is_string($ruta) || $ruta === '' || !is_file($ruta)) {
                throw new RuntimeException('Indica el CSV del paquete con --archivo="RUTA".');
            }
            if (!hash_equals(self::CSV_SHA256, hash_file('sha256', $ruta))) {
                throw new RuntimeException('El archivo CSV es distinto al revisado. No se realizó ningún cambio.');
            }
            $filas = $this->leerCsv($ruta);
            $aplicar = (bool) $this->option('aplicar');

            if ($aplicar) {
                $esperados = $this->option('esperados');
                if (!is_string($esperados) || !ctype_digit($esperados) || (int) $esperados > self::TOTAL_EXCEL) {
                    throw new RuntimeException('Para aplicar indica --esperados=N (número exacto obtenido en la revisión).');
                }
                $this->verificarRespaldo((string) $this->option('respaldo'));
                // Se coteja DE NUEVO con las filas bloqueadas dentro de la transacción.
                [$estados, $guardados] = DB::transaction(function () use ($filas, $esperados) {
                    $estados = $this->evaluar($filas, true);
                    $candidatos = count(array_filter($estados, fn ($r) => $r['estado'] === 'LISTO'));
                    if ($candidatos !== (int) $esperados) {
                        throw new RuntimeException("Cambió la situación desde la revisión: hay $candidatos candidatos, no $esperados. Nada importado.");
                    }
                    $guardados = 0;
                    foreach ($estados as $r) {
                        if ($r['estado'] !== 'LISTO') continue;
                        $n = DB::table('clientes')->where('id', $r['id'])
                            ->whereNull('latitud')->whereNull('longitud')
                            ->update(['latitud' => $r['latitud_excel'], 'longitud' => $r['longitud_excel']]);
                        if ($n !== 1) throw new RuntimeException('Falló la actualización protegida para '.$r['codigo'].'. Se revertirá toda la transacción.');
                        ++$guardados;
                    }
                    return [$estados, $guardados];
                }, 1);
                $this->resumen($estados);
                $this->info("OK: importadas $guardados ubicaciones GPS en clientes; transacción completada.");
                $this->info('OK: no se modificaron sedes, pagos, cargos, tarifas, historiales ni servicios.');
                $this->info('Verifica varias fichas en Clientes y su enlace de Google Maps.');
                return self::SUCCESS;
            }

            // Por defecto SOLAMENTE SELECT. Sin cambios en MySQL.
            $estados = $this->evaluar($filas, false);
            $this->resumen($estados);
            $this->escribirInforme($estados);
            $this->info('REVISIÓN SOLAMENTE: no se escribió ningún registro en MySQL.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('DETENIDO: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function leerCsv(string $archivo): array
    {
        $f = fopen($archivo, 'rb');
        if (!$f) throw new RuntimeException('No se pudo abrir CSV.');
        try {
            $cab = fgetcsv($f, 0, ',', '"', '');
            if (!$cab) throw new RuntimeException('CSV vacío.');
            $cab[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cab[0]);
            if ($cab !== ['codigo', 'nombre_excel', 'latitud', 'longitud']) {
                throw new RuntimeException('Las columnas del CSV no coinciden.');
            }
            $filas=[]; $codigos=[];
            while (($r=fgetcsv($f,0,',','"','')) !== false) {
                if ($r === [null]) continue;
                if (count($r)!==4)throw new RuntimeException('CSV con columnas incompletas.');
                [$codigo,$nombre,$lat,$lon]=array_map('trim',$r);
                $codigo=strtoupper($codigo);
                if (!preg_match('/^[A-Z0-9]{2,30}$/',$codigo) || $nombre==='' || isset($codigos[$codigo])) {
                    throw new RuntimeException('Código duplicado o inválido: '.$codigo);
                }
                if (!preg_match('/^-?\d{1,2}(?:\.\d{1,7})?$/',$lat) ||
                    !preg_match('/^-?\d{1,3}(?:\.\d{1,7})?$/',$lon) ||
                    abs((float)$lat)>90 || abs((float)$lon)>180) {
                    throw new RuntimeException('Coordenadas inválidas para '.$codigo);
                }
                $filas[]=['codigo'=>$codigo,'nombre_excel'=>$nombre,'latitud_excel'=>$lat,'longitud_excel'=>$lon];
                $codigos[$codigo]=true;
            }
            if (count($filas)!==self::TOTAL_EXCEL) {
                throw new RuntimeException('Se esperaban 64 filas, se encontraron '.count($filas));
            }
            return $filas;
        } finally { fclose($f); }
    }

    private function limpiarNombre(?string $s): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii(trim($s ?? ''))));
    }

    private function evaluar(array $filas, bool $bloquear): array
    {
        $salida=[];
        foreach ($filas as $r) {
            $consulta=DB::table('clientes')->where('codigo',$r['codigo']);
            if ($bloquear) $consulta->lockForUpdate();
            $encontrados=$consulta->get(['id','codigo','nombre','latitud','longitud']);
            $r['id']=null;$r['nombre_actual']='';$r['latitud_actual']='';$r['longitud_actual']='';
            if ($encontrados->count()===0) $r['estado']='NO_ENCONTRADO';
            elseif ($encontrados->count()!==1) $r['estado']='CODIGO_AMBIGUO';
            else {
                $c=$encontrados->first();
                $r['id']=$c->id;
                $r['nombre_actual']=(string)$c->nombre;
                $r['latitud_actual']=$c->latitud === null ? '' : (string)$c->latitud;
                $r['longitud_actual']=$c->longitud === null ? '' : (string)$c->longitud;
                if (strtoupper(trim((string)$c->codigo))!==$r['codigo'] ||
                    $this->limpiarNombre($r['nombre_excel'])==='' ||
                    $this->limpiarNombre($r['nombre_excel'])!==$this->limpiarNombre((string)$c->nombre)) {
                    $r['estado']='NOMBRE_O_CODIGO_DISTINTO';
                } elseif ($c->latitud===null && $c->longitud===null) {
                    $r['estado']='LISTO';
                } elseif ($c->latitud!==null && $c->longitud!==null &&
                    number_format((float)$c->latitud,7,'.','')===number_format((float)$r['latitud_excel'],7,'.','') &&
                    number_format((float)$c->longitud,7,'.','')===number_format((float)$r['longitud_excel'],7,'.','')) {
                    $r['estado']='YA_EXISTE_IGUAL';
                } else $r['estado']='GPS_EXISTENTE_NO_SOBRESCRITO';
            }
            $salida[]=$r;
        }
        return $salida;
    }

    private function resumen(array $estados): void
    {
        $cuentas=[];$codigos=[];
        foreach ($estados as $r) {
            $e=$r['estado']; $cuentas[$e]=($cuentas[$e]??0)+1;
            if ($e!=='LISTO') $codigos[$e][]=$r['codigo'];
        }
        $this->line('Excel: 64 ubicaciones; código y nombre deben coincidir con MySQL.');
        foreach (['LISTO','YA_EXISTE_IGUAL','GPS_EXISTENTE_NO_SOBRESCRITO','NOMBRE_O_CODIGO_DISTINTO','NO_ENCONTRADO','CODIGO_AMBIGUO'] as $e) {
            $this->line($e.': '.($cuentas[$e]??0));
            if (isset($codigos[$e]) && $codigos[$e])
                $this->line('  Codigos: '.implode(', ', $codigos[$e]));
        }
        $this->line('El listado detallado permite revisar nombres y coordenadas SIN importar automáticamente.');
    }

    private function escribirInforme(array $registros): void
    {
        // Se guarda FUERA del repositorio Laravel, nunca en public/ ni en GitHub.
        $carpeta=dirname(dirname(base_path())).DIRECTORY_SEPARATOR.'Respaldos-ISP';
        if (!is_dir($carpeta) && !mkdir($carpeta,0777,true) && !is_dir($carpeta)) {
            throw new RuntimeException('No se pudo crear la carpeta privada para el informe.');
        }
        $archivo=$carpeta.DIRECTORY_SEPARATOR.'revision_gps_'.date('Ymd_His').'.csv';
        $f=fopen($archivo,'xb');
        if (!$f) throw new RuntimeException('No se pudo crear el informe CSV fuera del proyecto.');
        try {
            fputcsv($f,['estado','codigo','nombre_excel','nombre_actual','gps_excel_lat','gps_excel_lon','gps_actual_lat','gps_actual_lon'],',','"','');
            foreach ($registros as $r) {
                fputcsv($f,[$r['estado'],$r['codigo'],$r['nombre_excel'],$r['nombre_actual'],$r['latitud_excel'],$r['longitud_excel'],$r['latitud_actual'],$r['longitud_actual']],',','"','');
            }
        } finally { fclose($f); }
        $this->info('Informe privado: '.$archivo);
    }

    private function verificarRespaldo(string $archivo): void
    {
        if ($archivo==='' || !is_file($archivo)) throw new RuntimeException('Para importar primero realiza un mysqldump reciente y pasa --respaldo="RUTA".');
        $real=realpath($archivo);$base=realpath(base_path());
        if (!$real || !$base || str_starts_with(strtolower(str_replace('\\','/',$real)),strtolower(str_replace('\\','/',$base)).'/')) {
            throw new RuntimeException('El respaldo debe existir FUERA de la carpeta del proyecto.');
        }
        if (filesize($archivo)<100000 || time()-filemtime($archivo)>14400 || filemtime($archivo)>time()+300) {
            throw new RuntimeException('El respaldo está vacío, incompleto o tiene más de cuatro horas.');
        }
        $f=fopen($archivo,'rb');
        if (!$f)throw new RuntimeException('No se puede leer el respaldo.');
        try {
            $cab=fread($f,1024);
            fseek($f,-min(2048,filesize($archivo)),SEEK_END);
            $pie=stream_get_contents($f);
        }finally{fclose($f);}
        if (!str_contains($cab,'MySQL dump') || !str_contains($pie,'-- Dump completed on')) {
            throw new RuntimeException('El respaldo SQL no tiene encabezado y cierre válidos de mysqldump.');
        }
        $this->info('OK: respaldo SQL completo, reciente y fuera del proyecto.');
    }
}
