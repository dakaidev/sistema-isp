<?php

use Illuminate\Support\Facades\Schedule;

// Al llegar el 1.º de mes, ejecutar en horario de Perú; si el equipo estaba apagado,
// la visita a /cobranza hace una recuperación del mes actual.
Schedule::command('isp:generar-mensualidades --aplicar --automatico')
    ->monthlyOn(1, '00:10')
    ->timezone('America/Lima')
    ->withoutOverlapping();
