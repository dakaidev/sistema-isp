<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_gasto')->index();
            $table->decimal('monto', 12, 2);
            $table->string('categoria', 40);
            $table->string('descripcion', 255);
            $table->string('medio', 30);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_at')->nullable();
            $table->string('motivo_anulacion', 255)->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_gastos');
    }
};
