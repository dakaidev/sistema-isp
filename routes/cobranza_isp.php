<?php

use App\Http\Middleware\CobranzaAutoMensual;
use App\Http\Middleware\EnsureIspPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', CobranzaAutoMensual::class])->group(function () {
    Route::view('/cobranza', 'cobranza.index')->middleware(EnsureIspPermission::class.':cobranza.view')->name('cobranza.index');
    Route::view('/cobranza/mensualidades', 'cobranza.mensualidades')->middleware(EnsureIspPermission::class.':facturacion.manage')->name('cobranza.mensualidades');
});
