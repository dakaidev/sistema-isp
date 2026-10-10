<?php

use Illuminate\Support\Facades\Route;

Route::view('/finanzas', 'finanzas.index')
    ->middleware(['auth', 'verified'])
    ->name('finanzas.index');
