<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Eliminacion extends Model
{
    protected $table = 'eliminaciones';

    protected $guarded = [];

    protected $casts = [
        'inicio' => 'date',
    ];

    /** @return BelongsTo<Negocio, $this> */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
