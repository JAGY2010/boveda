<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empenos', function (Blueprint $table) {
            $table->string('color')->nullable()->after('serial');
            $table->string('observaciones')->nullable()->after('color');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('contacto2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empenos', function (Blueprint $table) {
            $table->dropColumn(['color', 'observaciones']);
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('contacto2');
        });
    }
};
