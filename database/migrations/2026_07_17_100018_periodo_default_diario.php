<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El período por defecto es DIARIO: el interés es mensual y se cobra por día exacto.
        Schema::table('negocios', function (Blueprint $table) {
            $table->string('periodo', 20)->default('diario')->change();
        });

        // Reiniciar los que quedaron en 'mensual' por el default anterior (nadie lo eligió a propósito).
        DB::table('negocios')->where('periodo', 'mensual')->update(['periodo' => 'diario']);
        DB::table('empenos')->where('periodo', 'mensual')->update(['periodo' => 'diario']);
    }

    public function down(): void
    {
        // Sin reversa del default.
    }
};
