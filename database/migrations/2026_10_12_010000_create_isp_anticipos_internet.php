<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('isp_anticipos_internet', function (Blueprint $table) {
            $table->id();
            $table->uuid('operacion_uuid')->nullable()->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('pago_id')->constrained('isp_pagos')->restrictOnDelete();
            $table->foreignId('cargo_id')->nullable()->constrained('isp_cargos')->restrictOnDelete();
            $table->date('periodo');
            $table->decimal('monto', 10, 2);
            $table->decimal('monto_aplicado', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['pago_id', 'periodo'], 'isp_anticipos_pago_periodo_unique');
            $table->index(['cliente_id', 'periodo'], 'isp_anticipos_cliente_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_anticipos_internet');
    }
};
