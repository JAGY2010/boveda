<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('separados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventario_item_id')->constrained('inventario_items')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('precio');            // lo pactado con el cliente
            $table->bigInteger('abonado')->default(0);
            $table->string('estado')->default('activo'); // activo | entregado | cancelado
            $table->date('fecha_inicio');
            $table->date('fecha_cierre')->nullable();   // entrega o cancelacion
            $table->bigInteger('devuelto')->nullable(); // lo que se le regreso al cancelar
            $table->timestamps();
        });

        Schema::create('separado_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('separado_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('monto');
            $table->date('fecha');
            $table->timestamps();
        });

        Schema::table('negocios', function (Blueprint $table) {
            /* Los abonos entran a caja, pero todavia no son del negocio: si el
               cliente desiste hay que devolverlos. Esta bolsa dice cuanto de la
               caja esta comprometido, para que el total invertido no se infle. */
            $table->bigInteger('abonos_separados')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropColumn('abonos_separados');
        });
        Schema::dropIfExists('separado_abonos');
        Schema::dropIfExists('separados');
    }
};
