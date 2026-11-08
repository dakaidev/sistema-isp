<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La cuenta ID 1 es el administrador del respaldo ISP original.
        // Abortamos si no existe: evitar dejar a todos sin permisos.
        if (DB::table('users')->exists() && !DB::table('users')->where('id', 1)->exists()) {
            throw new RuntimeException('No existe el administrador principal (usuario ID 1). Migración detenida; revisa la base de datos.');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('visualizador');
            $table->boolean('activo')->default(true);
        });
        DB::table('users')->where('id', 1)->update(['rol' => 'admin', 'activo' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'activo']);
        });
    }
};
