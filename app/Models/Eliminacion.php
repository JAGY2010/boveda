<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eliminacion extends Model
{
    protected $table = 'eliminaciones';

    protected $guarded = [];

    protected $casts = [
        'inicio' => 'date',
    ];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }
}
