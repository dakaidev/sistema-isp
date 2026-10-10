<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_referencias_excel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->restrictOnDelete();
            $table->string('estado_excel', 35)->nullable();
            $table->decimal('deuda_excel', 10, 2)->nullable();
            $table->text('nota_excel')->nullable();
            $table->string('mensualidad_excel', 150)->nullable();
            $table->string('tecnologia_excel', 100)->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('isp_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->restrictOnDelete();
            $table->string('tipo', 30); // saldo_excel_verificado, cargo_manual...
            $table->string('categoria', 20); // internet, equipo, otros
            $table->string('descripcion', 255);
            $table->decimal('monto', 10, 2);
            $table->date('fecha_vencimiento');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['cliente_id', 'fecha_vencimiento']);
        });

        Schema::create('isp_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('categoria', 20); // internet, equipo, otros
            $table->string('medio', 30);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['cliente_id', 'fecha_pago']);
        });

        Schema::create('isp_aplicaciones_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('isp_pagos')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('isp_cargos')->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->timestamps();
            $table->unique(['pago_id', 'cargo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_aplicaciones_pago');
        Schema::dropIfExists('isp_pagos');
        Schema::dropIfExists('isp_cargos');
        Schema::dropIfExists('isp_referencias_excel');
    }
};
