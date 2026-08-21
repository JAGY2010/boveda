<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
