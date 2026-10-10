
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Identificación del cliente
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 160);
            $table->string('documento', 20)->nullable();

            // Contacto
            $table->string('telefono', 20)->nullable();

            // Ubicación
            $table->string('sector', 120)->nullable();
            $table->text('direccion')->nullable();

            // Información adicional
            $table->text('observaciones')->nullable();

            // Fechas del registro
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
