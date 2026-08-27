<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un bloque de números de contrato apartado para un dispositivo, para que
 * pueda crear empeños sin conexión sin chocar con los de otro equipo.
 */
class RangoNumero extends Model
{
    protected $table = 'rangos_numero';

    protected $guarded = [];

    /** @return BelongsTo<Negocio, $this> */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
