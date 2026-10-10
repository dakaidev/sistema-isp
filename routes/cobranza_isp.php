<?php

use App\Http\Middleware\CobranzaAutoMensual;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', CobranzaAutoMensual::class])->group(function () {
    Route::view('/cobranza', 'cobranza.index')->name('cobranza.index');
    Route::view('/cobranza/mensualidades', 'cobranza.mensualidades')->name('cobranza.mensualidades');
});
