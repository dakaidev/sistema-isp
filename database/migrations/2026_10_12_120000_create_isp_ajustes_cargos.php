<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('isp_ajustes_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('isp_cargos')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('monto_anterior', 10, 2);
            $table->decimal('monto_nuevo', 10, 2);
            $table->text('motivo');
            $table->timestamps();
            $table->index(['cliente_id', 'cargo_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('isp_ajustes_cargos');
    }
};