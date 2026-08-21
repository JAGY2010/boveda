<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    protected $table = 'negocios';

    protected $guarded = [];

    /** Días antes del vencimiento en que se muestra el aviso al usuario. */
    public const DIAS_AVISO = 3;

    protected $casts = [
        'sms_activo' => 'boolean',
        'pct_default' => 'decimal:2',
        'suscripcion_hasta' => 'date',
        'suspendido' => 'boolean',
    ];

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Todos los usuarios con acceso al local (vía pivote negocio_user). */
    /** @return BelongsToMany<User, $this> */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** @return HasMany<Cliente, $this> */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    /** @return HasMany<Empeno, $this> */
    public function empenos(): HasMany
    {
        return $this->hasMany(Empeno::class);
    }

    /** @return HasMany<InventarioItem, $this> */
    public function inventario(): HasMany
    {
        return $this->hasMany(InventarioItem::class);
    }

    /** @return HasMany<Gasto, $this> */
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    /** @return HasMany<Movimiento, $this> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    /** @return HasMany<Eliminacion, $this> */
    public function eliminaciones(): HasMany
    {
        return $this->hasMany(Eliminacion::class);
    }

    /** @return HasMany<Separado, $this> */
    public function separados(): HasMany
    {
        return $this->hasMany(Separado::class);
    }

    // ---- Suscripción (estado calculado en vivo desde suscripcion_hasta + suspendido) ----

    /** Días para el vencimiento: >0 quedan días, 0 vence hoy, <0 ya venció, null = sin control. */
    public function diasParaVencer(): ?int
    {
        if (! $this->suscripcion_hasta) {
            return null;
        }

        // Hoy en hora local, no en UTC: si no, de noche la suscripcion
        // parecia vencer un dia antes.
        return (int) ahoraLocal()->startOfDay()
            ->diffInDays($this->suscripcion_hasta->startOfDay(), false);
    }

    /** activa | por_vencer | vencida | suspendida (en ese orden de prioridad). */
    public function estadoSuscripcion(): string
    {
        if ($this->suspendido) {
            return 'suspendida';
        }

        $d = $this->diasParaVencer();

        if ($d === null) {
            return 'activa';
        }
        if ($d < 0) {
            return 'vencida';
        }
        if ($d <= self::DIAS_AVISO) {
            return 'por_vencer';
        }

        return 'activa';
    }

    public function suscripcionPorVencer(): bool
    {
        return $this->estadoSuscripcion() === 'por_vencer';
    }

    public function suscripcionBloqueada(): bool
    {
        return in_array($this->estadoSuscripcion(), ['vencida', 'suspendida'], true);
    }

    /** Texto legible del estado para el panel admin. */
    public function estadoLabel(): string
    {
        return match ($this->estadoSuscripcion()) {
            'suspendida' => 'Suspendida',
            'vencida' => 'Vencida hace '.abs($this->diasParaVencer()).' día(s)',
            'por_vencer' => 'Por vencer · '.$this->diasParaVencer().' día(s)',
            default => 'Al día'.($this->suscripcion_hasta ? ' · vence '.$this->suscripcion_hasta->format('d/m/Y') : ''),
        };
    }

    /**
     * Total invertido = las tres bolsas de capital de trabajo, menos lo que
     * la caja debe.
     *
     * Los abonos de los separados ya entraron a caja, pero el articulo sigue
     * contado en el inventario y esa plata podria haber que devolverla: sin
     * restarla, el mismo valor se contaria dos veces.
     */
    public function totalInvertido(): int
    {
        return $this->caja + $this->prestado + $this->inventario_valor - $this->abonos_separados;
    }

    public function gananciaBruta(): int
    {
        return $this->acum_interes + $this->acum_margen;
    }

    public function gananciaNeta(): int
    {
        return $this->gananciaBruta() - $this->acum_gastos;
    }
}
