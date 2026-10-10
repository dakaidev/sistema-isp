<?php

namespace App\Http\Middleware;

use App\Services\Cobranza\GeneradorMensualidades;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Si se abre Cobranza después del día 1, crear las mensualidades del mes en curso pendientes. */
class CobranzaAutoMensual
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo al consultar la página, nunca al previsualizar o guardar formularios.
        if ($request->isMethod('GET') &&
            DB::table('isp_mensualidades_generadas')->exists() &&
            DB::table('isp_configuracion_mensual')->where('habilitado', true)->exists()) {
            try {
                app(GeneradorMensualidades::class)->ejecutar(today('America/Lima')->format('Y-m'), true);
            } catch (\Throwable $e) {
                Log::error('Fallo al generar mensualidades ISP al abrir Cobranza', ['exception' => $e]);
                session()->flash('advertencia_facturacion',
                    'No se pudieron actualizar las mensualidades automáticas. Ejecuta la simulación en Mensualidades y revisa el log.');
            }
        }
        return $next($request);
    }
}
