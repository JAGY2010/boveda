<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_venta')->nullable();
        });

        Schema::table('empenos', function (Blueprint $table) {
            $table->date('pct_desde')->nullable()->after('pct');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->dropColumn(['fecha_compra', 'fecha_venta']);
        });
        Schema::table('empenos', function (Blueprint $table) {
            $table->dropColumn('pct_desde');
        });
    }
};
