<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'codigo',
        'nombre',
        'documento',
        'telefono',
        'sector',
        'sede',
        'latitud',
        'longitud',
        'direccion',
        'fecha_instalacion',
        'fecha_baja',
        'observaciones',
    ];

        
    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_baja' => 'date',
        ];
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

}
