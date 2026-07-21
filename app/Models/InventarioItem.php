<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioItem extends Model
{
    protected $table = 'inventario_items';

    protected $guarded = [];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_venta' => 'date',
    ];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }

    public function empeno()
    {
        return $this->belongsTo(Empeno::class);
    }
}
