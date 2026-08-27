<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de operaciones ya ejecutadas.
 *
 * Cada formulario viaja con una clave única. Si la misma clave llega dos
 * veces —porque se fue el internet a mitad de camino, porque el empleado
 * volvió a darle al botón, o porque la cola sin conexión reenvió lo que
 * tenía pendiente— la segunda no vuelve a cobrar: se responde con el
 * resultado de la primera.
 *
 * Sin esto, una cola de operaciones sin conexión es peligrosa con dinero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ruta')->nullable();
            $table->string('destino')->nullable(); // a donde llevo la operacion
            $table->boolean('completada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones');
    }
};
