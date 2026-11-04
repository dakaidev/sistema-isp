<?php

use App\Http\Middleware\CobranzaAutoMensual;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', CobranzaAutoMensual::class])->group(function () {
    Route::view('/cobranza', 'cobranza.index')->middleware(\App\Http\Middleware\EnsureIspPermission::class.':cobranza.view')->name('cobranza.index');
    Route::view('/cobranza/mensualidades', 'cobranza.mensualidades')->middleware(\App\Http\Middleware\EnsureIspPermission::class.':facturacion.manage')->name('cobranza.mensualidades');
});
