<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El número de contrato solo estaba protegido por una validación en la
 * aplicación. Dos empeños creados en el mismo instante podían quedar con
 * el mismo número, y ese número va impreso en el contrato que firma el
 * cliente. Aquí se pone la garantía donde no se puede saltar: la base.
 *
 * Es ademas el cimiento para repartir rangos de numeros por dispositivo
 * cuando se trabaje sin conexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repetidos = DB::table('empenos')
            ->select('negocio_id', 'numero', DB::raw('count(*) as veces'))
            ->groupBy('negocio_id', 'numero')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($repetidos->isNotEmpty()) {
            /* Renumerar un contrato ya firmado es una decision del negocio,
               no de una migracion: se corta aqui y se avisa cual es. */
            $detalle = $repetidos
                ->map(fn ($r) => "local {$r->negocio_id} numero {$r->numero} ({$r->veces} veces)")
                ->implode('; ');

            throw new RuntimeException(
                'Hay numeros de contrato repetidos y no se puede crear el indice unico. '.
                'Corrijelos a mano antes de desplegar: '.$detalle
            );
        }

        Schema::table('empenos', function (Blueprint $table) {
            $table->unique(['negocio_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::table('empenos', function (Blueprint $table) {
            $table->dropUnique(['negocio_id', 'numero']);
        });
    }
};
