<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_configuracion_mensual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->restrictOnDelete();
            $table->decimal('tarifa_mensual', 10, 2)->nullable();
            $table->date('inicio_cobro');
            $table->boolean('habilitado')->default(false);
            $table->timestamps();
        });

        Schema::create('isp_mensualidades_generadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('cargo_id')->unique()->constrained('isp_cargos')->restrictOnDelete();
            $table->date('periodo'); // Primer día del mes; independiente de created_at
            $table->decimal('tarifa_base', 10, 2); // Copia histórica: no cambia al editar tarifa futura
            $table->timestamps();
            $table->unique(['cliente_id', 'periodo'], 'isp_mensualidad_cliente_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_mensualidades_generadas');
        Schema::dropIfExists('isp_configuracion_mensual');
    }
};
