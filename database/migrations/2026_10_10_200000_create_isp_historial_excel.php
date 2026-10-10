<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_historial_excel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->date('periodo'); // Mes de la columna: NO es la fecha exacta de un depósito.
            $table->decimal('importe_anotado', 12, 2); // Importe del Excel, incluso cero explícito.
            $table->string('nombre_excel', 255);
            $table->string('celda_excel', 12);
            $table->string('fuente', 70)->default('clientesUpina (1).xlsx');
            $table->timestamps();
            $table->unique(['cliente_id', 'periodo'], 'isp_historial_excel_cliente_periodo_unique');
            $table->index(['periodo', 'importe_anotado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_historial_excel');
    }
};
