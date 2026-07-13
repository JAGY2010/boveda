<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }
}
