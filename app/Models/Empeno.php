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
        'pct_desde' => 'date',
        'fecha_retiro' => 'date',
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
    public function interesCorrido(?string $periodo = null): int
    {
        /* `cubiertoHasta()` sale de `inicio`, que es un cast 'date': una
           fecha de calendario SIN hora, que no se convierte de zona. Lo
           que si habia que arreglar es el otro lado: `now()` devolvia UTC
           y a partir de las 7 p.m. contaba un dia de mas.

           Como abajo se redondea HACIA ARRIBA al bloque, ese dia de mas
           podia saltar un bloque entero: el cliente que llegaba el dia
           justo en que se cumplia su mes terminaba pagando dos. */
        $dias = (int) $this->cubiertoHasta()->startOfDay()
            ->diffInDays(ahoraLocal()->startOfDay(), false);
        if ($dias <= 0) {
            return 0;
        }

        // Por defecto usa el período del empeño; en el retiro el empleado puede
        // elegir otro bloque (día/semana/quincena/mes) solo para ese cobro.
        $u = $periodo ? diasDelPeriodo($periodo) : $this->diasPeriodo();
        $diasCobrados = (int) (ceil($dias / $u) * $u); // redondeo hacia arriba al bloque

        return redondearCien($this->interesMes() * $diasCobrados / 30);
    }

    /** Cuánto debe hoy para retirar = saldo + interés corrido. */
    public function deudaHoy(?string $periodo = null): int
    {
        return $this->saldo + $this->interesCorrido($periodo);
    }

    /** Meses acumulados sin pagar (seguidos o no). */
    public function mesesSinPagar(): int
    {
        $transcurridos = (int) $this->inicio->diffInMonths(ahoraLocal());

        return max(0, $transcurridos - $this->meses_pagados);
    }

    // ---- Historia del artículo (resumen de toda la vida del empeño) ----

    /** Suma de los abonos a capital hechos durante el empeño. */
    public function totalAbonos(): int
    {
        return (int) $this->pagos->sum('abono');
    }

    /** Suma de los intereses cobrados en los pagos mensuales (sin el retiro final). */
    public function totalInteresPagos(): int
    {
        return (int) $this->pagos->sum('interes');
    }

    /**
     * Interés cobrado en el retiro final. El saldo solo baja con abonos, así que
     * el saldo al momento de retirar = principal − abonos; lo demás fue interés.
     */
    public function interesRetiro(): int
    {
        if ($this->valor_retiro === null) {
            return 0;
        }

        $saldoAlRetirar = (int) $this->principal - $this->totalAbonos();

        return max(0, (int) $this->valor_retiro - $saldoAlRetirar);
    }

    /** Todo lo que el cliente pagó en intereses durante la vida del empeño. */
    public function totalIntereses(): int
    {
        return $this->totalInteresPagos() + $this->interesRetiro();
    }

    /** Todo lo que el cliente entregó al negocio (intereses + abonos + retiro). */
    public function totalPagado(): int
    {
        return $this->totalInteresPagos() + $this->totalAbonos() + (int) $this->valor_retiro;
    }

    /** Días que el artículo estuvo en el negocio. */
    public function diasEnPrenda(): int
    {
        /* `fecha_retiro` es cast 'date'; si no hay retiro se usa hoy en
           hora local, no en UTC. */
        $fin = $this->fecha_retiro ?: ahoraLocal();

        return max(0, (int) $this->inicio->startOfDay()
            ->diffInDays($fin->copy()->startOfDay()));
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
