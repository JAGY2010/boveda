<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function empeno()
    {
        return $this->belongsTo(Empeno::class);
    }
}
