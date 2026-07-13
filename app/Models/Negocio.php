<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    protected $table = 'negocios';

    protected $guarded = [];

    protected $casts = [
        'sms_activo' => 'boolean',
        'pct_default' => 'decimal:2',
    ];

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function clientes(): HasMany { return $this->hasMany(Cliente::class); }
    public function empenos(): HasMany { return $this->hasMany(Empeno::class); }
    public function inventario(): HasMany { return $this->hasMany(InventarioItem::class); }
    public function gastos(): HasMany { return $this->hasMany(Gasto::class); }
    public function movimientos(): HasMany { return $this->hasMany(Movimiento::class); }

    /** Total invertido = las tres bolsas de capital de trabajo. */
    public function totalInvertido(): int
    {
        return $this->caja + $this->prestado + $this->inventario_valor;
    }

    public function gananciaBruta(): int
    {
        return $this->acum_interes + $this->acum_margen;
    }

    public function gananciaNeta(): int
    {
        return $this->gananciaBruta() - $this->acum_gastos;
    }
}
