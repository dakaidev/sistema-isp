<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // NULL para clientes antiguos: su sede se confirma individualmente.
            $table->string('sede', 20)->nullable()->after('sector')->index();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['sede']);
            $table->dropColumn('sede');
        });
    }
};
