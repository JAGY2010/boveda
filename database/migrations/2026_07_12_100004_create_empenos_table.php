<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empenos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('categoria')->nullable();
            $table->json('atributos')->nullable();
            $table->string('articulo');
            $table->string('serial')->nullable();
            $table->bigInteger('principal');       // valor prestado inicial
            $table->bigInteger('saldo');           // capital pendiente
            $table->decimal('pct', 6, 2);          // interes mensual %
            $table->unsignedTinyInteger('plazo');  // meses
            $table->date('inicio');
            $table->unsignedInteger('meses_pagados')->default(0);
            $table->string('estado')->default('activo'); // activo|retirado|perdido|vendido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empenos');
    }
};
