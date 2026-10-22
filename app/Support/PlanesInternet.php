<?php

namespace App\Support;

/** Planes de referencia para la interfaz; no cambian la facturacion por si mismos. */
final class PlanesInternet
{
    public const CATALOGO = [
        'inicio' => ['nombre' => 'Inicio', 'velocidad' => 5, 'precio' => 35],
        'conecta' => ['nombre' => 'Conecta', 'velocidad' => 8, 'precio' => 50],
        'avanza' => ['nombre' => 'Avanza', 'velocidad' => 10, 'precio' => 60],
        'turbo' => ['nombre' => 'Turbo', 'velocidad' => 20, 'precio' => 70],
    ];

    public static function identificar(?string $nombre, ?int $velocidad): string
    {
        foreach (self::CATALOGO as $clave => $plan) {
            if ($velocidad === $plan['velocidad'] && mb_strtolower(trim((string) $nombre)) === mb_strtolower($plan['nombre'])) {
                return $clave;
            }
        }
        return 'personalizado';
    }
}
