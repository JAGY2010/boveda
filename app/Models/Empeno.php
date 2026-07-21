<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Empeno extends Model
{
    protected $table = 'empenos';

    protected $guarded = [];

    protected $casts = [
        'atributos' => 'array',
        'inicio' => 'date',
        'pct' => 'decimal:2',
    ];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class)->orderBy('fecha')->orderBy('id');
    }

    // ---- Lógica del empeño (validada en el prototipo) ----

    // ---- Período de COBRO: el interés es mensual; esto solo define en qué bloque
    //      de días se cobra cuando el cliente llega (por día, semana, quincena o mes),
    //      redondeando los días HACIA ARRIBA al bloque. Ej. quincenal: a los 10 días
    //      cobra los 15 completos "así no se hayan cumplido".

    /** Días del bloque de cobro: diario=1, semanal=7, quincenal=15, mensual=30. Por defecto diario. */
    public function diasPeriodo(): int
    {
        return diasDelPeriodo($this->periodo ?: 'diario');
    }

    public function periodoLabel(): string
    {
        return periodoLabel($this->periodo ?: 'diario');
    }

    /** Interés de un mes = % sobre el saldo actual (redondeado al cien). */
    public function interesMes(): int
    {
        return redondearCien($this->saldo * $this->pct / 100);
    }

    /** Vencimiento = inicio + plazo + (nº de cuotas de interés pagadas), en MESES. */
    public function vencimiento(): CarbonInterface
    {
        return $this->inicio->copy()->addMonthsNoOverflow($this->plazo + $this->meses_pagados);
    }

    /** Fecha hasta la que el interés está cubierto (en MESES). */
    public function cubiertoHasta(): CarbonInterface
    {
        return $this->inicio->copy()->addMonthsNoOverflow($this->meses_pagados);
    }

    /**
     * Interés corrido = lo que debe pagar hoy además del saldo. El interés es mensual
     * (cuota/30 por día); los días transcurridos se redondean HACIA ARRIBA al bloque de
     * cobro del local (día, semana, quincena o mes). Sigue corriendo más allá de un mes.
     */
    public function interesCorrido(): int
    {
        $dias = (int) $this->cubiertoHasta()->startOfDay()->diffInDays(now()->startOfDay(), false);
        if ($dias <= 0) {
            return 0;
        }

        $u = $this->diasPeriodo();
        $diasCobrados = (int) (ceil($dias / $u) * $u); // redondeo hacia arriba al bloque

        return redondearCien($this->interesMes() * $diasCobrados / 30);
    }

    /** Cuánto debe hoy para retirar = saldo + interés corrido. */
    public function deudaHoy(): int
    {
        return $this->saldo + $this->interesCorrido();
    }

    /** Meses acumulados sin pagar (seguidos o no). */
    public function mesesSinPagar(): int
    {
        $transcurridos = (int) $this->inicio->diffInMonths(now());

        return max(0, $transcurridos - $this->meses_pagados);
    }

    /** Estado calculado: al dia | en mora | por perder | (o el estado cerrado). */
    public function estadoCalculado(): string
    {
        if ($this->estado !== 'activo') {
            return $this->estado;
        }

        $m = $this->mesesSinPagar();

        if ($m >= $this->plazo) {
            return 'por perder';
        }

        if ($m >= 1) {
            return 'en mora';
        }

        return 'al dia';
    }
}
