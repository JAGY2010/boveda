<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eliminaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->unsignedInteger('numero')->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->string('articulo')->nullable();
            $table->unsignedBigInteger('principal')->default(0);
            $table->date('inicio')->nullable();
            $table->string('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eliminaciones');
    }
};
