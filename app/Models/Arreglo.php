<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plata invertida en un artículo del inventario para poder venderlo
 * (repuestos, mano de obra). Suma a su costo, no a los gastos.
 */
class Arreglo extends Model
{
    protected $table = 'arreglos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    /** @return BelongsTo<InventarioItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventarioItem::class, 'inventario_item_id');
    }

    /** @return BelongsTo<Negocio, $this> */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
