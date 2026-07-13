<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ciudad')->nullable();
            $table->string('nit')->nullable();
            $table->unsignedTinyInteger('plazo_default')->default(4);
            $table->decimal('pct_default', 6, 2)->default(20);
            $table->unsignedTinyInteger('ltv_default')->default(50);
            $table->boolean('sms_activo')->default(true);

            // Las 4 bolsas del dinero (en pesos colombianos, enteros)
            $table->bigInteger('caja')->default(0);
            $table->bigInteger('prestado')->default(0);
            $table->bigInteger('inventario_valor')->default(0);

            // Acumulados para las ganancias
            $table->bigInteger('acum_interes')->default(0);
            $table->bigInteger('acum_margen')->default(0);
            $table->bigInteger('acum_gastos')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
