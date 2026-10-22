<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoNap extends Model
{
    protected $table = 'isp_nap_movimientos';

    protected $fillable = ['caja_nap_id', 'numero_puerto', 'tipo', 'detalle', 'usuario_id'];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CajaNap::class, 'caja_nap_id');
    }
}
