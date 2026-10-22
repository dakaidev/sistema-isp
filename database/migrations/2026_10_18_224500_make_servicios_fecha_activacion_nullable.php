<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ninguna fecha se inventa. Conservar las fechas antiguas y permitir NULL en altas nuevas.
        Schema::table('servicios', function (Blueprint $table) {
            $table->date('fecha_activacion')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No convertir a obligatoria si hay servicios sin fecha: seria una perdida de datos.
        if (DB::table('servicios')->whereNull('fecha_activacion')->exists()) {
            throw new RuntimeException('No se puede revertir: hay conexiones con activacion sin confirmar.');
        }
        Schema::table('servicios', function (Blueprint $table) {
            $table->date('fecha_activacion')->nullable(false)->change();
        });
    }
};
