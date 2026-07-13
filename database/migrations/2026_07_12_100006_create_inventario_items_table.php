<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('empeno_id')->nullable()->constrained()->nullOnDelete();
            $table->string('descripcion');
            $table->bigInteger('costo');
            $table->string('origen'); // perdido | compra
            $table->string('estado')->default('disponible'); // disponible | vendido
            $table->bigInteger('venta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_items');
    }
};
