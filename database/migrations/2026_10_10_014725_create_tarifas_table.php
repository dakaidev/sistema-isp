<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {

            $table->id();

            // Servicio al que pertenece la tarifa
            $table->foreignId('servicio_id')
                ->constrained('servicios')
                ->restrictOnDelete();

            // Importe mensual en soles
            $table->decimal('monto', 10, 2);

            // Fecha desde la que se aplica
            $table->date('vigente_desde');

            // Justificación del cambio
            $table->string('motivo', 255);

            // Usuario que registró el cambio
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Una tarifa por servicio y fecha de inicio
            $table->unique([
                'servicio_id',
                'vigente_desde',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
