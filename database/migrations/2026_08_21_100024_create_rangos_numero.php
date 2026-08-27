<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rangos de números de contrato reservados por dispositivo.
 *
 * Sin conexión no se puede preguntar "cuál es el siguiente número": dos
 * equipos contestarían lo mismo y saldrían dos contratos con el mismo
 * número, ya firmados. Por eso cada dispositivo pide por adelantado un
 * bloque de números que son solo suyos y los va gastando sin conexión.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rangos_numero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->string('dispositivo');   // identificador que guarda el navegador
            $table->unsignedInteger('desde');
            $table->unsignedInteger('hasta');
            $table->timestamps();

            // Dos dispositivos no pueden tener el mismo bloque.
            $table->unique(['negocio_id', 'desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rangos_numero');
    }
};
