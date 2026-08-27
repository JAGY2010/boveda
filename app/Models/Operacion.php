<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una operación de escritura ya ejecutada, identificada por su clave.
 * Sirve para no cobrar dos veces lo mismo cuando algo se reenvía.
 */
class Operacion extends Model
{
    protected $table = 'operaciones';

    protected $guarded = [];

    protected $casts = [
        'completada' => 'boolean',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
