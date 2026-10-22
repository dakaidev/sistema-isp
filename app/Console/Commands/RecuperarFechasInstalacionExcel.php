<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Fecha de primera instalación histórica del cliente. No mueve pagos ni tarifas. */
class RecuperarFechasInstalacionExcel extends Command
{
    protected $signature = 'isp:revisar-fechas-instalacion
        {--archivo= : CSV de fechas de instalación del Excel}
        {--aplicar : Importar únicamente coincidencias confirmadas con fecha vacía}
        {--respaldo= : Respaldo SQL reciente obligatorio para aplicar}
        {--esperados= : Número de coincidencias LISTO de la revisión}';
    protected $description = 'Revisa 139 fechas del Excel e importa solo coincidencias por código y nombre, con respaldo.';

    private const CSV_SHA256 = 'ad76cc2541d9f81e3b5ed5f5228c674cbdc88e2a82be7d1d6757fbb4e0e64ea2';
    private const TOTAL_EXCEL = 139;

    public function handle(): int
    {
        try {
            $archivo = $this->option('archivo');
            if (!is_string($archivo) || !is_file($archivo) || !hash_equals(self::CSV_SHA256, hash_file('sha256', $archivo))) {
                throw new RuntimeException('El CSV falta o no coincide con el original del paquete. Nada cambiado.');
            }
            $filas = $this->leerCsv($archivo);
            if ($this->option('aplicar')) {
                $esperados = $this->option('esperados');
                if (!is_string($esperados) || !ctype_digit($esperados) || (int) $esperados > self::TOTAL_EXCEL) {
                    throw new RuntimeException('Debes indicar --esperados=N con el valor LISTO mostrado en la revisión.');
                }
                $this->verificarRespaldo((string)$this->option('respaldo'));
                [$estados,$cantidad] = DB::transaction(function () use ($filas,$esperados) {
                    $estados=$this->evaluar($filas,true);
                    $listos=count(array_filter($estados,fn($r)=>$r['estado']==='LISTO'));
                    if ($listos!==(int)$esperados) throw new RuntimeException('Cambió la cantidad de coincidencias; no se importó ninguna fecha.');
                    $cantidad=0;
                    foreach($estados as $r) {
                        if ($r['estado']!=='LISTO') continue;
                        $n=DB::table('clientes')->where('id',$r['id'])->whereNull('fecha_instalacion')
                           ->update(['fecha_instalacion'=>$r['fecha_excel']]);
                        if ($n!==1) throw new RuntimeException('Falló el registro '.$r['codigo'].'; se revierte todo.');
                        $cantidad++;
                    }
                    return [$estados,$cantidad];
                },3);
                $this->resumir($estados);
                $this->info("OK: $cantidad fechas de instalación recuperadas. No se tocaron pagos, servicios, sedes, cajas NAP ni fechas de baja.");
            } else {
                $estados=$this->evaluar($filas,false);
                $this->resumir($estados);
                $this->informe($estados);
                $this->info('REVISIÓN SOLAMENTE: no se escribió nada en MySQL.');
            }
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('DETENIDO: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function leerCsv(string $ruta): array
    {
        $f=fopen($ruta,'rb');
        if (!$f) throw new RuntimeException('No se pudo leer CSV.');
        try {
            $cab=fgetcsv($f,0,',','"','');
            if ($cab!==['codigo','nombre_excel','fecha_instalacion']) throw new RuntimeException('Cabecera CSV no válida.');
            $salida=[];$codigos=[];
            while(($r=fgetcsv($f,0,',','"',''))!==false){
                if($r===[null]) continue;
                if(count($r)!==3) throw new RuntimeException('Fila incompleta en CSV.');
                [$codigo,$nombre,$fecha]=array_map('trim',$r);
                $codigo=strtoupper($codigo);
                if(!preg_match('/^[A-Z0-9]{2,30}$/',$codigo) || $nombre==='' || isset($codigos[$codigo])) throw new RuntimeException('Código inválido o duplicado: '.$codigo);
                $dt=\DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
                if(!$dt || $dt->format('Y-m-d')!==$fecha) throw new RuntimeException('Fecha inválida para '.$codigo);
                $salida[]=['codigo'=>$codigo,'nombre_excel'=>$nombre,'fecha_excel'=>$fecha];$codigos[$codigo]=true;
            }
            if(count($salida)!==self::TOTAL_EXCEL) throw new RuntimeException('Cantidad CSV incorrecta, esperados 139.');
            return $salida;
        } finally {fclose($f);}
    }

    private function normalizar(?string $x):string
    {
        return preg_replace('/[^a-z0-9]+/','',strtolower(Str::ascii(trim($x??''))));
    }

    private function evaluar(array $filas,bool $bloquear):array
    {
        $resultado=[];
        foreach($filas as $r){
            $q=DB::table('clientes')->where('codigo',$r['codigo']);
            if($bloquear)$q->lockForUpdate();
            $clientes=$q->get(['id','codigo','nombre','fecha_instalacion','fecha_baja']);
            $r['id']=null;$r['nombre_actual']='';$r['fecha_actual']='';$r['fecha_baja_actual']='';
            if($clientes->count()===0)$r['estado']='NO_ENCONTRADO';
            elseif($clientes->count()>1)$r['estado']='CODIGO_AMBIGUO';
            else{
                $c=$clientes->first();$r['id']=$c->id;
                $r['nombre_actual']=(string)$c->nombre;
                $r['fecha_actual']=(string)($c->fecha_instalacion??'');
                $r['fecha_baja_actual']=(string)($c->fecha_baja??'');
                if(strtoupper((string)$c->codigo)!==$r['codigo'] || $this->normalizar($r['nombre_excel'])==='' || $this->normalizar($r['nombre_excel'])!==$this->normalizar((string)$c->nombre))$r['estado']='NOMBRE_O_CODIGO_DISTINTO';
                elseif($c->fecha_instalacion!==null){
                    $r['estado']=substr((string)$c->fecha_instalacion,0,10)===$r['fecha_excel']?'YA_EXISTE_IGUAL':'FECHA_EXISTENTE_NO_SOBRESCRITA';
                }elseif($c->fecha_baja!==null && substr((string)$c->fecha_baja,0,10)<$r['fecha_excel'])$r['estado']='BAJA_ANTERIOR_A_INSTALACION';
                else $r['estado']='LISTO';
            }
            $resultado[]=$r;
        }
        return $resultado;
    }

    private function resumir(array $registros):void
    {
        $cuentas=[];
        foreach($registros as $r)$cuentas[$r['estado']]=($cuentas[$r['estado']]??0)+1;
        $this->line('Excel original: '.self::TOTAL_EXCEL.' fechas de instalación. La columna Baja estaba vacía.');
        foreach(['LISTO','YA_EXISTE_IGUAL','FECHA_EXISTENTE_NO_SOBRESCRITA','BAJA_ANTERIOR_A_INSTALACION','NOMBRE_O_CODIGO_DISTINTO','NO_ENCONTRADO','CODIGO_AMBIGUO'] as $estado){
            $this->line($estado.': '.($cuentas[$estado]??0));
            if(!in_array($estado,['LISTO','YA_EXISTE_IGUAL'],true)){
                $co=array_column(array_filter($registros,fn($r)=>$r['estado']===$estado),'codigo');
                if($co)$this->line('  Codigos: '.implode(', ',$co));
            }
        }
    }

    private function informe(array $registros):void
    {
        $folder=dirname(dirname(base_path())).DIRECTORY_SEPARATOR.'Respaldos-ISP';
        if(!is_dir($folder) && !mkdir($folder,0770,true) && !is_dir($folder))throw new RuntimeException('No se pudo crear Respaldos-ISP.');
        $path=$folder.DIRECTORY_SEPARATOR.'revision_fechas_instalacion_'.date('Ymd_His').'.csv';
        $f=fopen($path,'xb');if(!$f)throw new RuntimeException('No se pudo crear informe.');
        try{
            fputcsv($f,['estado','codigo','nombre_excel','nombre_actual','fecha_excel','fecha_actual','fecha_baja_actual'],',','"','');
            foreach($registros as $r)fputcsv($f,[$r['estado'],$r['codigo'],$r['nombre_excel'],$r['nombre_actual'],$r['fecha_excel'],$r['fecha_actual'],$r['fecha_baja_actual']],',','"','');
        }finally{fclose($f);}
        $this->info('Informe privado: '.$path);
    }

    private function verificarRespaldo(string $ruta):void
    {
        if($ruta==='' || !is_file($ruta))throw new RuntimeException('Antes de importar, realiza mysqldump y pasa --respaldo=RUTA.');
        $real=realpath($ruta);$base=realpath(base_path());
        if(!$real || !$base || str_starts_with(strtolower(str_replace('\\','/',$real)),strtolower(str_replace('\\','/',$base)).'/'))throw new RuntimeException('Respaldo debe quedar fuera del proyecto.');
        if(filesize($ruta)<100000 || time()-filemtime($ruta)>14400 || filemtime($ruta)>time()+300)throw new RuntimeException('Respaldo vacío, incompleto o antiguo.');
        $f=fopen($ruta,'rb');if(!$f)throw new RuntimeException('No se pudo abrir el respaldo.');
        try{$cab=fread($f,1024);fseek($f,-min(2048,filesize($ruta)),SEEK_END);$pie=stream_get_contents($f);}finally{fclose($f);}
        if(!str_contains($cab,'MySQL dump') || !str_contains($pie,'-- Dump completed on'))throw new RuntimeException('Respaldo incompleto.');
        $this->info('OK: respaldo SQL reciente y completo verificado.');
    }
}
