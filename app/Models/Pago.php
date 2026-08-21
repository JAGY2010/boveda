<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    /** @return BelongsTo<Empeno, $this> */
    public function empeno(): BelongsTo
    {
        return $this->belongsTo(Empeno::class);
    }
}
