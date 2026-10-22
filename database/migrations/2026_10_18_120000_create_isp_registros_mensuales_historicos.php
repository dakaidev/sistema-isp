<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_registros_mensuales_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->date('periodo'); // Mes anotado; NO es fecha de cobro.
            $table->decimal('importe', 12, 2); // Incluye S/ 0 para conservar los meses.
            $table->unique(['cliente_id', 'periodo'], 'isp_registros_hist_cliente_periodo_uq');
            $table->index('periodo');
        });
    }

    public function down(): void
    {
        // No eliminar datos historicos al retroceder migraciones accidentalmente.
        throw new RuntimeException('El historial migrado requiere restauracion explicita desde un respaldo.');
    }
};
