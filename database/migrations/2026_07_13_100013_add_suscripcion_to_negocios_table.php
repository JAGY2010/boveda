<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->date('suscripcion_hasta')->nullable()->after('consecutivo_inicial'); // pagado hasta (inclusive)
            $table->boolean('suspendido')->default(false)->after('suscripcion_hasta');    // corte manual del admin
            $table->string('plan', 30)->nullable()->after('suspendido');                  // opcional
            $table->unsignedBigInteger('precio_mensual')->nullable()->after('plan');       // COP, opcional
        });

        // No bloquear locales existentes al desplegar: 30 días de gracia desde hoy.
        DB::table('negocios')->whereNull('suscripcion_hasta')
            ->update(['suscripcion_hasta' => now()->addDays(30)->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn(['suscripcion_hasta', 'suspendido', 'plan', 'precio_mensual']);
        });
    }
};
