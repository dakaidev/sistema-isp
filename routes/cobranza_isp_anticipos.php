<?php

use App\Http\Middleware\EnsureIspPermission;
use App\Models\Cliente;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->get('/cobranza/ficha/{cliente}/adelantos', function (Cliente $cliente) {
    return view('cobranza.pago-adelantado', ['cliente' => $cliente]);
})->middleware(EnsureIspPermission::class.':cobranza.pay')
    ->name('cobranza.adelantos');
