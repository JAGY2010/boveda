<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empeno_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tipo'); // interes | interes + abono
            $table->bigInteger('interes')->default(0);
            $table->bigInteger('abono')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
