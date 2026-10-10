<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('isp_imputaciones_pagos_meses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('pago_id')->constrained('isp_pagos')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('isp_cargos')->restrictOnDelete();
            $table->date('periodo');
            $table->decimal('monto', 10, 2);
            $table->timestamps();
            $table->unique(['pago_id', 'cargo_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('isp_imputaciones_pagos_meses');
    }
};
