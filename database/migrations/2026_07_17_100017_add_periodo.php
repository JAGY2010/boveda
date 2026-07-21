<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            // Período de cobro por defecto del local: diario | semanal | quincenal | mensual.
            $table->string('periodo', 20)->default('mensual')->after('plazo_default');
        });

        Schema::table('empenos', function (Blueprint $table) {
            // Cada empeño recuerda con qué período nació (para no descuadrar los viejos si cambian la config).
            $table->string('periodo', 20)->nullable()->after('plazo');
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('periodo');
        });
        Schema::table('empenos', function (Blueprint $table) {
            $table->dropColumn('periodo');
        });
    }
};
