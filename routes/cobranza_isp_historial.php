<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/cobranza/historial-excel', 'cobranza.historial-excel')
        ->name('cobranza.historial-excel');
});
