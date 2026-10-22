<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuertoNap extends Model
{
    protected $table = 'isp_nap_puertos';

    protected $fillable = ['caja_nap_id', 'numero', 'estado', 'observaciones'];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CajaNap::class, 'caja_nap_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
