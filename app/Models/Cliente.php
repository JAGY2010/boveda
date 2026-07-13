<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $guarded = [];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }

    public function empenos()
    {
        return $this->hasMany(Empeno::class);
    }
}
