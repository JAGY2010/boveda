<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioItem extends Model
{
    protected $table = 'inventario_items';

    protected $guarded = [];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_venta' => 'date',
    ];

    /** @return BelongsTo<Negocio, $this> */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /** @return BelongsTo<Empeno, $this> */
    public function empeno(): BelongsTo
    {
        return $this->belongsTo(Empeno::class);
    }

    /** @return HasMany<Arreglo, $this> */
    public function arreglos(): HasMany
    {
        return $this->hasMany(Arreglo::class, 'inventario_item_id')->orderBy('fecha')->orderBy('id');
    }

    /** Lo que se invirtió en arreglos (ya está sumado en el costo). */
    public function totalArreglos(): int
    {
        return (int) $this->arreglos->sum('monto');
    }

    /** Lo que costó al comprarlo o al pasar del empeño, sin arreglos. */
    public function costoOriginal(): int
    {
        return (int) $this->costo - $this->totalArreglos();
    }
}
