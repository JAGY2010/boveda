<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un artículo apartado: el cliente lo va abonando y se lo lleva
 * cuando termina de pagarlo.
 */
class Separado extends Model
{
    protected $table = 'separados';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_cierre' => 'date',
    ];

    /** @return BelongsTo<Negocio, $this> */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /** @return BelongsTo<InventarioItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventarioItem::class, 'inventario_item_id');
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return HasMany<SeparadoAbono, $this> */
    public function abonos(): HasMany
    {
        return $this->hasMany(SeparadoAbono::class)->orderBy('fecha')->orderBy('id');
    }

    /** Lo que falta para poder llevarse el artículo. */
    public function saldo(): int
    {
        return max(0, (int) $this->precio - (int) $this->abonado);
    }

    public function estaPago(): bool
    {
        return $this->saldo() === 0;
    }

    /** Porcentaje abonado, para la barra de progreso (0-100). */
    public function porcentaje(): int
    {
        if ((int) $this->precio <= 0) {
            return 100;
        }

        return (int) min(100, round((int) $this->abonado * 100 / (int) $this->precio));
    }
}
