<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'cliente_id',
        'tipo_conexion',
        'nombre_plan',
        'velocidad_mbps',
        'tarifa_mensual',
        'fecha_activacion',
        'estado',
        'direccion_instalacion',
        'ip_asignada',
        'mac_equipo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_activacion' => 'date',
            'tarifa_mensual' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
