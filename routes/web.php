<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});


Route::view('/clientes', 'clientes.index')
    ->middleware(['auth', 'verified'])
    ->name('clientes.index');

Route::view('/infraestructura/cajas-nap', 'nap.index')
    ->middleware(['auth', 'verified'])
    ->name('nap.index');

Route::view('/servicios', 'servicios.index')
    ->middleware(['auth', 'verified'])
    ->name('servicios.index');

    
Route::view('/clientes/nuevo', 'clientes.index')
    ->middleware(['auth', 'verified'])
    ->name('clientes.nuevo');

Route::view('/clientes/{cliente}/editar', 'clientes.index')
    ->middleware(['auth', 'verified'])
    ->name('clientes.editar');

Route::get('/clientes/{cliente}', function (\App\Models\Cliente $cliente) {

    $cliente->load([
        'servicios' => fn ($query) => $query->latest('id'),
    ]);

    return view('clientes.show', [
        'cliente' => $cliente,
        'resumenFicha' => \App\Support\ResumenFichaCliente::obtener((int) $cliente->id),
    ]);

})
    ->middleware(['auth', 'verified'])
    ->name('clientes.show');

    
require __DIR__.'/cobranza_isp.php';
require __DIR__.'/cobranza_isp_ficha.php';
require __DIR__.'/cobranza_isp_periodos.php';

require __DIR__.'/finanzas_isp.php';

require __DIR__.'/settings.php';
require __DIR__.'/cobranza_isp_anticipos.php';
