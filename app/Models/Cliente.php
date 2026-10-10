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
        'direccion',
        'observaciones',
    ];

        
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

}
