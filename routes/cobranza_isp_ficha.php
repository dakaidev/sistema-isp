<?php

use App\Models\Cliente;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->get('/cobranza/ficha/{cliente}', function (Cliente $cliente) {
    return view('cobranza.ficha', ['cliente' => $cliente]);
})->name('cobranza.ficha');
