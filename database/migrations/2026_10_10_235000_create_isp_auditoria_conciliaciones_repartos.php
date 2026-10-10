<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_auditoria_conciliaciones_repartos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reparto_original_id')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('pago_id')->constrained('isp_pagos')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('isp_cargos')->restrictOnDelete();
            $table->date('periodo');
            $table->decimal('monto', 10, 2);
            $table->string('motivo', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_auditoria_conciliaciones_repartos');
    }
};
