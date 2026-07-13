<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->string('representante')->nullable()->after('nit');
            $table->string('direccion')->nullable()->after('representante');
            $table->string('telefono')->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['representante', 'direccion', 'telefono']);
        });
    }
};
