<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->get('/cobranza/pago/{pagoId}/distribuir', function (int $pagoId) {
    return view('cobranza.distribuir-pago', ['pagoId' => $pagoId]);
})->middleware(\App\Http\Middleware\EnsureIspPermission::class.':cobranza.view')
    ->name('cobranza.distribuir-pago');
