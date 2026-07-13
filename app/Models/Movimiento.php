<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }
}
