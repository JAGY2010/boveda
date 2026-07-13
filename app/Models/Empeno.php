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

    /** Interés de un mes = % sobre el saldo actual. */
    public function interesMes(): int
    {
        return (int) round($this->saldo * $this->pct / 100);
    }

    /** Vencimiento = inicio + plazo + (nº de cuotas de interés pagadas). */
    public function vencimiento(): CarbonInterface
    {
        return $this->inicio->copy()->addMonthsNoOverflow($this->plazo + $this->meses_pagados);
    }

    /** Fecha hasta la que el interés está cubierto. */
    public function cubiertoHasta(): CarbonInterface
    {
        return $this->inicio->copy()->addMonthsNoOverflow($this->meses_pagados);
    }

    /** Interés corrido (prorrateado por día) desde la última cuota cubierta. */
    public function interesCorrido(): int
    {
        $dias = $this->cubiertoHasta()->diffInDays(now(), false);
        if ($dias <= 0) {
            return 0;
        }

        return (int) round($this->saldo * ($this->pct / 100) * ($dias / 30));
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
