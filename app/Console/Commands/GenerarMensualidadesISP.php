<?php

namespace App\Console\Commands;

use App\Services\Cobranza\GeneradorMensualidades;
use Illuminate\Console\Command;

class GenerarMensualidadesISP extends Command
{
    protected $signature = 'isp:generar-mensualidades {--mes= : Mes AAAA-MM, por defecto mes actual de Perú} {--aplicar : Crear los cargos realmente} {--automatico : No ejecutar hasta que exista una primera emisión manual}';
    protected $description = 'Simula o crea una mensualidad por cliente habilitado. Seguro al ejecutar varias veces.';

    public function handle(GeneradorMensualidades $generador): int
    {
        $mes = $this->option('mes') ?: today('America/Lima')->format('Y-m');
        if ($this->option('automatico') && !\Illuminate\Support\Facades\DB::table('isp_mensualidades_generadas')->exists()) {
            $this->line('Automático pendiente: primero simula y genera un mes manualmente.');
            return self::SUCCESS;
        }
        try {
            $r = $generador->ejecutar($mes, (bool) $this->option('aplicar'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info('Período: ' . $r['mes']);
        $this->line('Clientes habilitados para facturar este mes: ' . $r['habilitados']);
        $this->line('Mensualidades nuevas: ' . $r['nuevos']);
        $this->line('Ya generadas (sin duplicar): ' . $r['existentes']);
        $this->line('Clientes sin configuración habilitada: ' . $r['sin_configurar']);
        $this->line('Total de cargos nuevos: S/ ' . $r['total']);
        $this->warn($this->option('aplicar') ? 'GENERACIÓN APLICADA.' : 'MODO SIMULACIÓN: NO se modificó MySQL.');
        return self::SUCCESS;
    }
}
