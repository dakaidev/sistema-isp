<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaNap extends Model
{
    protected $table = 'isp_cajas_nap';

    protected $fillable = [
        'sede', 'codigo', 'capacidad', 'latitud', 'longitud',
        'referencia', 'observaciones', 'activa',
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'activa' => 'boolean',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    public function puertos(): HasMany
    {
        return $this->hasMany(PuertoNap::class, 'caja_nap_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoNap::class, 'caja_nap_id');
    }
}
