<?php
/** Instalador de cambios puntuales. Ejecutar desde la carpeta raiz de Laravel. */
declare(strict_types=1);
$root = getcwd();
$paths = [
    'routes/web.php',
    'app/Livewire/Cobranza/FichaCliente.php',
    'app/Livewire/Cobranza/DistribuirPago.php',
    'app/Services/Cobranza/GeneradorMensualidades.php',
    'resources/views/livewire/cobranza/ficha-cliente.blade.php',
];
$newFiles = [
    'routes/cobranza_isp_anticipos.php',
    'app/Livewire/Cobranza/PagoAdelantado.php',
    'app/Services/Cobranza/AplicadorAnticiposInternet.php',
    'database/migrations/2026_10_12_010000_create_isp_anticipos_internet.php',
    'resources/views/livewire/cobranza/pago-adelantado.blade.php',
    'resources/views/cobranza/pago-adelantado.blade.php',
];
foreach (array_merge($paths, $newFiles) as $relative) {
    if (!is_file($root . '/' . $relative)) {
        fwrite(STDERR, "FALTA: $relative. No se ha modificado codigo existente.\n");
        exit(1);
    }
}
$changes = [];
foreach ($paths as $relative) {
    $changes[$relative] = file_get_contents($root . '/' . $relative);
}
function once(string &$source, string $needle, string $replace, string $label): void
{
    $count = substr_count($source, $needle);
    if ($count !== 1) throw new RuntimeException("No reconozco el bloque '$label' (encontrados: $count). No se aplicó nada. Necesito revisar tu versión del archivo.");
    $source = str_replace($needle, $replace, $source);
}
try {
    $ruta = &$changes[$paths[0]];
    if (str_contains($ruta, "cobranza_isp_anticipos.php")) throw new RuntimeException('El módulo ya parece instalado. No instalar dos veces.');
    $ruta = rtrim($ruta) . "\nrequire __DIR__.'/cobranza_isp_anticipos.php';\n";
    unset($ruta);

    $ficha = &$changes[$paths[1]];
    once($ficha,
        "        \$resumen = [\n",
        "        \$anticiposSinAplicar = DB::table('isp_anticipos_internet')->where('cliente_id', \$this->clienteId)\n            ->selectRaw('COALESCE(SUM(monto - monto_aplicado), 0) as saldo')->value('saldo');\n        \$resumen = [\n",
        'resumen de la ficha');
    once($ficha,
        "'credito' => max(0, \$pagado - \$totalAplicado - \$pagosDistribuidosHistoricos) / 100,",
        "'credito' => max(0, \$pagado - \$totalAplicado - \$pagosDistribuidosHistoricos - \$this->centavos(\$anticiposSinAplicar)) / 100,",
        'no confundir adelantos con pagos libres');
    once($ficha,
        "        \$exoneraciones = DB::table('isp_exoneraciones_periodos')",
        "        \$anticipos = DB::table('isp_anticipos_internet')->where('cliente_id', \$this->clienteId)->orderBy('periodo')->get();\n        \$exoneraciones = DB::table('isp_exoneraciones_periodos')",
        'consulta de anticipos');
    once($ficha,
        "'exoneraciones', 'opcionesPago', 'imputacionesDirectas'",
        "'exoneraciones', 'opcionesPago', 'imputacionesDirectas', 'anticipos'",
        'paso de anticipos a la vista');
    unset($ficha);

    $distribuir = &$changes[$paths[2]];
    once($distribuir,
        "            \$disponible = \$this->centavos(\$pago->monto) - \$aplicadoCargo - \$aplicadoMes;",
        "            \$reservado = \$this->centavos(DB::table('isp_anticipos_internet')->where('pago_id', \$pago->id)\n                ->selectRaw('COALESCE(SUM(monto - monto_aplicado), 0) as total')->value('total'));\n            \$disponible = \$this->centavos(\$pago->monto) - \$aplicadoCargo - \$aplicadoMes - \$reservado;",
        'blindaje de distribución del pago');
    once($distribuir,
        "        \$sinAsignar = max(0, \$this->centavos(\$pago->monto) - \$aplicado)/100;",
        "        \$reservado = \$this->centavos(DB::table('isp_anticipos_internet')->where('pago_id', \$pago->id)\n            ->selectRaw('COALESCE(SUM(monto - monto_aplicado), 0) as total')->value('total'));\n        \$sinAsignar = max(0, \$this->centavos(\$pago->monto) - \$aplicado - \$reservado)/100;",
        'saldo disponible al distribuir');
    unset($distribuir);

    $generador = &$changes[$paths[3]];
    // Las versiones antiguas consumian creditos sin respetar el mes. Desactivar ese cruce.
    $generador = str_replace("                        \$this->aplicarCreditoInternet((int) \$plan->cliente_id);\n", '', $generador);
    once($generador,
        "                        return [true, \$centavos];",
        "                        // Aplicar EXCLUSIVAMENTE dinero reservado para esta mensualidad.\n                        app(AplicadorAnticiposInternet::class)->alGenerar((int) \$plan->cliente_id, (int) \$idCargo, \$inicio->toDateString());\n                        return [true, \$centavos];",
        'aplicación automática de anticipos en el generador');
    unset($generador);

    $vista = &$changes[$paths[4]];
    once($vista,
        '>Registrar pago nuevo</button>',
        '>Registrar pago nuevo</button>\n                <a href="{{ route(\'cobranza.adelantos\', [\'cliente\' => $cliente->id]) }}" class="rounded-xl border border-blue-500 px-4 py-2.5 text-sm font-bold text-blue-200 hover:bg-blue-950">Pago de varios meses / adelantado</a>',
        'botón nuevo al lado del pago normal');
    once($vista,
        "                ->merge(\$imputacionesDirectas->collapse()->pluck('periodo'))",
        "                ->merge(\$imputacionesDirectas->collapse()->pluck('periodo'))\n                ->merge(\$anticipos->pluck('periodo'))",
        'meses reservados en el historial');
    once($vista,
        "                        \$pagosAplicadosAlMes = \$imputacionesDirectas->collapse()->filter(fn (\$a) => substr((string)\$a->periodo, 0, 7) === \$mes);",
        "                        \$pagosAplicadosAlMes = \$imputacionesDirectas->collapse()->filter(fn (\$a) => substr((string)\$a->periodo, 0, 7) === \$mes);\n                        \$anticiposDelMes = \$anticipos->filter(fn (\$a) => substr((string)\$a->periodo, 0, 7) === \$mes);",
        'anticipos por mes');
    once($vista,
        "                        @foreach (\$exoneracionesMes as \$e)",
        <<<'BLADE'
                        @foreach ($anticiposDelMes as $adelanto)
                            <div class="mt-2 border-t border-zinc-800 pt-2 text-xs text-sky-300">
                                Pago #{{ $adelanto->pago_id }} reservado para este mes: S/ {{ number_format((float)$adelanto->monto, 2) }}.
                                <span class="text-zinc-400">{{ (float)$adelanto->monto_aplicado > 0 ? 'Aplicado al cargo: S/ '.number_format((float)$adelanto->monto_aplicado,2) : 'Esperando mensualidad (no es otro ingreso)' }}</span>
                                @if ((float)$adelanto->monto_aplicado > 0 && (float)$adelanto->monto_aplicado < (float)$adelanto->monto)
                                    <span class="text-amber-300">Queda reserva sin aplicar: S/ {{ number_format((float)$adelanto->monto - (float)$adelanto->monto_aplicado, 2) }}; revisar cambio de tarifa.</span>
                                @endif
                            </div>
                        @endforeach
                        @foreach ($exoneracionesMes as $e)
BLADE,
        'etiquetas de los adelantos en el historial');
    unset($vista);
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: {$e->getMessage()}\nNingún archivo existente fue modificado.\n");
    exit(1);
}
// Valida primero cada cambio y respalda todo antes de escribir.
$stamp = date('Ymd_His');
foreach ($paths as $relative) {
    if (!copy($root . '/' . $relative, $root . '/' . $relative . '.antes-adelantos-' . $stamp . '.bak')) {
        fwrite(STDERR, "ERROR: No se pudo crear respaldo de $relative; no hay modificaciones.\n");
        exit(1);
    }
}
foreach ($changes as $relative => $contenido) {
    if (file_put_contents($root . '/' . $relative, $contenido) === false) {
        fwrite(STDERR, "ERROR al guardar $relative. Restaura sus copias .bak.\n");
        exit(1);
    }
    echo "Actualizado: $relative\n";
}
echo "OK. Ahora ejecuta php artisan migrate y php artisan view:clear. NO se han modificado registros de MySQL.\n";
