<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'servicio_id',
        'monto',
        'vigente_desde',
        'motivo',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'vigente_desde' => 'date',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
