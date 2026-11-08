<?php

use App\Http\Middleware\EnsureIspPermission;
use App\Models\Cliente;
use App\Support\ResumenFichaCliente;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->middleware(EnsureIspPermission::class.':dashboard.view')
        ->name('dashboard');
});

Route::view('/clientes', 'clientes.index')
    ->middleware(['auth', 'verified'])
    ->middleware(EnsureIspPermission::class.':clientes.view')
    ->name('clientes.index');

Route::view('/infraestructura/cajas-nap', 'nap.index')
    ->middleware(['auth', 'verified'])
    ->middleware(EnsureIspPermission::class.':nap.view')
    ->name('nap.index');

Route::view('/servicios', 'servicios.index')
    ->middleware(['auth', 'verified'])
    ->middleware(EnsureIspPermission::class.':servicios.view')
    ->name('servicios.index');

Route::view('/clientes/nuevo', 'clientes.index')
        ->middleware(['auth', 'verified'])
        ->middleware(EnsureIspPermission::class.':clientes.edit')
        ->name('clientes.nuevo');

Route::view('/clientes/{cliente}/editar', 'clientes.index')
    ->middleware(['auth', 'verified'])
    ->middleware(EnsureIspPermission::class.':clientes.edit')
    ->name('clientes.editar');

Route::get('/clientes/{cliente}', function (Cliente $cliente) {

    $cliente->load([
        'servicios' => fn ($query) => $query->latest('id'),
    ]);

    return view('clientes.show', [
        'cliente' => $cliente,
        'resumenFicha' => ResumenFichaCliente::obtener((int) $cliente->id),
    ]);

})
    ->middleware(['auth', 'verified'])
    ->middleware(EnsureIspPermission::class.':clientes.view')
    ->name('clientes.show');

require __DIR__.'/cobranza_isp.php';
require __DIR__.'/cobranza_isp_ficha.php';
require __DIR__.'/cobranza_isp_periodos.php';

require __DIR__.'/finanzas_isp.php';

require __DIR__.'/settings.php';
require __DIR__.'/cobranza_isp_anticipos.php';

// Administración de cuentas; solo administrador.
Route::view('/usuarios', 'usuarios.index')
    ->middleware(['auth', 'verified', EnsureIspPermission::class.':usuarios.manage'])
    ->name('usuarios.index');
