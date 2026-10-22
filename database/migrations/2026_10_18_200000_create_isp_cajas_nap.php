<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_cajas_nap', function (Blueprint $table) {
            $table->id();
            $table->string('sede', 20); // upina | calasuca (validado desde la aplicación)
            $table->string('codigo', 80); // Ejemplo ilustrativo: ULTRA 05
            $table->unsignedTinyInteger('capacidad'); // 8 o 16
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('referencia', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->unique(['sede', 'codigo'], 'isp_nap_sede_codigo_unico');
            $table->index('sede');
        });

        Schema::create('isp_nap_puertos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_nap_id')->constrained('isp_cajas_nap')->restrictOnDelete();
            $table->unsignedTinyInteger('numero');
            // Sin verificar por defecto; jamás asumimos que un puerto real está libre.
            $table->string('estado', 24)->default('sin_verificar');
            $table->text('observaciones')->nullable();
            // Reserva para futura relación con instalaciones/servicios, no se usa en esta fase.
            $table->foreignId('servicio_id')->nullable()->unique()
                ->constrained('servicios')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['caja_nap_id', 'numero'], 'isp_nap_caja_puerto_unico');
        });

        Schema::create('isp_nap_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_nap_id')->constrained('isp_cajas_nap')->restrictOnDelete();
            $table->unsignedTinyInteger('numero_puerto')->nullable();
            $table->string('tipo', 50);
            $table->text('detalle');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['caja_nap_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Protegemos la infraestructura real: nunca borrar cajas registradas en un rollback.
        if (Schema::hasTable('isp_cajas_nap') && DB::table('isp_cajas_nap')->exists()) {
            throw new \RuntimeException('No se permite revertir Cajas NAP: hay cajas reales registradas.');
        }
        Schema::dropIfExists('isp_nap_movimientos');
        Schema::dropIfExists('isp_nap_puertos');
        Schema::dropIfExists('isp_cajas_nap');
    }
};
