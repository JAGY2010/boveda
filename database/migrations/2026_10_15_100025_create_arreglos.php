<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arreglos hechos a un artículo del inventario (una llanta, una batería,
 * pintura...). No son gastos del negocio: es plata invertida en ESE artículo,
 * así que suben su costo. Cuando se venda, la ganancia ya sale descontada.
 *
 * Se guarda cada uno con su descripción para saber de dónde sale el costo,
 * no solo el número final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arreglos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventario_item_id')->constrained('inventario_items')->cascadeOnDelete();
            $table->string('descripcion');
            $table->bigInteger('monto');
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arreglos');
    }
};
