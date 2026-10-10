<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {

            $table->id();

            // Cliente propietario del servicio
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete();

            // Datos de la conexión
            $table->string('tipo_conexion', 30);
            $table->string('nombre_plan', 100)->nullable();
            $table->unsignedInteger('velocidad_mbps')->nullable();

            // Mensualidad individual
            $table->decimal('tarifa_mensual', 10, 2);

            // Activación y estado del servicio
            $table->date('fecha_activacion');
            $table->string('estado', 30)->default('activo');

            // Ubicación y detalles técnicos
            $table->text('direccion_instalacion')->nullable();
            $table->string('ip_asignada', 45)->nullable();
            $table->string('mac_equipo', 30)->nullable();

            // Información adicional
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
