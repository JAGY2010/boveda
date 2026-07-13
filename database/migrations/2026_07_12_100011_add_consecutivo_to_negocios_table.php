<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            // Número con el que arranca el talonario de cada local (numeración propia).
            $table->unsignedInteger('consecutivo_inicial')->default(1)->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('consecutivo_inicial');
        });
    }
};
