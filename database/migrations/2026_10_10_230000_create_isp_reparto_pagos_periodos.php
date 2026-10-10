<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_repartos_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('pago_id')->constrained('isp_pagos')->restrictOnDelete();
            $table->date('periodo');
            $table->decimal('monto', 10, 2);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
            $table->unique(['pago_id', 'periodo']);
            $table->index(['cliente_id', 'periodo']);
        });

        Schema::create('isp_exoneraciones_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->date('periodo');
            $table->string('motivo', 255);
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['cliente_id', 'periodo']);
        });

        Schema::create('isp_correcciones_aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aplicacion_original_id')->unique();
            $table->foreignId('pago_id')->constrained('isp_pagos')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('isp_cargos')->restrictOnDelete();
            $table->decimal('monto_original', 10, 2);
            $table->string('motivo', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_correcciones_aplicaciones');
        Schema::dropIfExists('isp_exoneraciones_periodos');
        Schema::dropIfExists('isp_repartos_periodos');
    }
};
