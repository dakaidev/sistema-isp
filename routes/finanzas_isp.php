<?php

use App\Http\Middleware\EnsureIspPermission;
use Illuminate\Support\Facades\Route;

Route::view('/finanzas', 'finanzas.index')
    ->middleware(['auth', 'verified'])
    ->middleware(EnsureIspPermission::class.':finanzas.cobros')
    ->name('finanzas.index');
