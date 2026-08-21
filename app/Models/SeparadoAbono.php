<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeparadoAbono extends Model
{
    protected $table = 'separado_abonos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    /** @return BelongsTo<Separado, $this> */
    public function separado(): BelongsTo
    {
        return $this->belongsTo(Separado::class);
    }
}
