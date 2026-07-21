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

    // ---- Período de cobro (diario | semanal | quincenal | mensual) ----

    /** Días del período de este empeño (mensual = 30 comercial para prorratear). */
    public function diasPeriodo(): int
    {
        return diasDelPeriodo($this->periodo);
    }

    public function esMensual(): bool
    {
        return ($this->periodo ?: 'mensual') === 'mensual';
    }

    public function periodoLabel(): string
    {
        return periodoLabel($this->periodo);
    }

    /** Avanza N períodos desde una fecha: mensual por calendario, los demás por días fijos. */
    private function avanzar(CarbonInterface $fecha, int $n): CarbonInterface
    {
        return $this->esMensual()
            ? $fecha->copy()->addMonthsNoOverflow($n)
            : $fecha->copy()->addDays($n * $this->diasPeriodo());
    }

    /** Interés de un período = % sobre el saldo actual (redondeado al cien). */
    public function interesMes(): int
    {
        return redondearCien($this->saldo * $this->pct / 100);
    }

    /** Vencimiento = inicio + plazo + (nº de cuotas pagadas), en períodos. */
    public function vencimiento(): CarbonInterface
    {
        return $this->avanzar($this->inicio, $this->plazo + $this->meses_pagados);
    }

    /** Fecha hasta la que el interés está cubierto. */
    public function cubiertoHasta(): CarbonInterface
    {
        return $this->avanzar($this->inicio, $this->meses_pagados);
    }

    /** Interés corrido POR DÍA exacto dentro del período, redondeado al cien más cercano. */
    public function interesCorrido(): int
    {
        // Días completos transcurridos (el mismo día no cobra nada).
        $dias = (int) $this->cubiertoHasta()->startOfDay()->diffInDays(now()->startOfDay(), false);
        if ($dias <= 0) {
            return 0;
        }

        // Interés por día = cuota del período / días del período × días. Sigue corriendo más allá de un período.
        return redondearCien($this->interesMes() * $dias / $this->diasPeriodo());
    }

    /** Cuánto debe hoy para retirar = saldo + interés corrido. */
    public function deudaHoy(): int
    {
        return $this->saldo + $this->interesCorrido();
    }

    /** Períodos transcurridos desde el inicio (mensual por calendario, los demás por días). */
    private function periodosTranscurridos(): int
    {
        if ($this->esMensual()) {
            return (int) $this->inicio->diffInMonths(now());
        }

        $dias = (int) $this->inicio->startOfDay()->diffInDays(now()->startOfDay(), false);

        return max(0, intdiv($dias, $this->diasPeriodo()));
    }

    /** Períodos (cuotas) acumulados sin pagar (seguidos o no). */
    public function mesesSinPagar(): int
    {
        return max(0, $this->periodosTranscurridos() - $this->meses_pagados);
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
