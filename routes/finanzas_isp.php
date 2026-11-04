<?php

use Illuminate\Support\Facades\Route;

Route::view('/finanzas', 'finanzas.index')
    ->middleware(['auth', 'verified'])
    ->middleware(\App\Http\Middleware\EnsureIspPermission::class.':finanzas.cobros')
    ->name('finanzas.index');
