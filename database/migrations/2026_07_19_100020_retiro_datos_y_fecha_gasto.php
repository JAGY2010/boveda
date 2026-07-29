<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Al retirar solo se marcaba el estado: se perdían la fecha y el valor pagado.
        Schema::table('empenos', function (Blueprint $table) {
            $table->date('fecha_retiro')->nullable()->after('estado');
            $table->bigInteger('valor_retiro')->nullable()->after('fecha_retiro');
        });

        // Recuperar los retiros ya hechos desde el movimiento de caja que sí quedó.
        DB::table('empenos')->where('estado', 'retirado')->orderBy('id')->each(function ($e) {
            $mov = DB::table('movimientos')
                ->where('negocio_id', $e->negocio_id)
                ->where('descripcion', "Retiro empeño #{$e->numero}")
                ->orderByDesc('id')
                ->first();

            if ($mov) {
                DB::table('empenos')->where('id', $e->id)->update([
                    'fecha_retiro' => $mov->fecha,
                    'valor_retiro' => $mov->monto,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('empenos', function (Blueprint $table) {
            $table->dropColumn(['fecha_retiro', 'valor_retiro']);
        });
    }
};
