<?php

namespace App\Support;

/**
 * Unifica la vista OPERATIVA del registro historico por mes de servicio y los
 * recibos posteriores fechados. No sustituye a un libro de caja auditado.
 * Sin coincidencia cliente/mes se suman. En coincidencias desconocidas se usa
 * el mayor y se marca para revision; las conciliaciones expresas del administrador
 * se suman solamente si codigo, periodo e importes siguen coincidiendo.
 */
final class ResumenIngresosMensuales
{
    /**
     * Casos verificados por el administrador el 18/10/2026.
     * La mensualidad antigua y el adelanto son cobros DIFERENTES.
     * El mes del adelanto es la fecha de recepcion, no el mes del servicio.
     * Las cifras en centimos impiden dar por conciliado un registro modificado.
     * No se registra una fecha exacta no disponible para el pago historico.
     */
    private const CONCILIACIONES_CONFIRMADAS = [
        '2026-09|CU165' => [
            'historico' => 6000, 'internet' => 21000,
            'nota' => 'Septiembre: S/ 60 de mensualidad anterior y S/ 210 recibidos el 30/09 como adelanto de octubre, noviembre y diciembre (S/ 70 cada mes).',
        ],
        '2026-09|CU228' => [
            'historico' => 6000, 'internet' => 7000,
            'nota' => 'Septiembre: S/ 60 de mensualidad anterior y S/ 70 recibidos el 30/09 como adelanto de octubre.',
        ],
    ];
    public static function calcular(iterable $pagos, iterable $historicos): array
    {
        $grupos = [];
        $crear = static function (int $id, string $periodo, string $codigo, string $nombre) use (&$grupos): string {
            $clave = $periodo . '|' . $id;
            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'periodo' => $periodo, 'cliente_id' => $id,
                    'codigo' => $codigo, 'nombre' => $nombre,
                    'anterior' => 0, 'internet' => 0, 'otros' => 0,
                ];
            }
            return $clave;
        };
        foreach ($historicos as $h) {
            $periodo = substr((string) $h->periodo, 0, 7);
            $clave = $crear((int) $h->cliente_id, $periodo, (string) $h->codigo, (string) $h->nombre);
            $grupos[$clave]['anterior'] += (int) round((float) $h->importe * 100);
        }
        foreach ($pagos as $p) {
            $periodo = substr((string) $p->fecha_pago, 0, 7);
            $clave = $crear((int) $p->cliente_id, $periodo, (string) $p->codigo, (string) $p->nombre);
            $campo = (string) $p->categoria === 'internet' ? 'internet' : 'otros';
            $grupos[$clave][$campo] += (int) round((float) $p->monto * 100);
        }
        $sumas = array_fill(1, 12, 0);
        $pendientes = array_fill(1, 12, 0);
        $detalles = array_fill(1, 12, []);
        foreach ($grupos as $g) {
            $mes = (int) substr($g['periodo'], 5, 2);
            if ($mes < 1 || $mes > 12) continue;
            $coinciden = $g['anterior'] > 0 && $g['internet'] > 0;
            $claveConfirmada = $g['periodo'] . '|' . strtoupper(trim($g['codigo']));
            $regla = self::CONCILIACIONES_CONFIRMADAS[$claveConfirmada] ?? null;
            $confirmado = $coinciden && $regla !== null
                && $g['anterior'] === $regla['historico']
                && $g['internet'] === $regla['internet'];
            $monto = ($confirmado
                ? $g['anterior'] + $g['internet']
                : max($g['anterior'], $g['internet'])) + $g['otros'];
            $sumas[$mes] += $monto;
            if ($coinciden && !$confirmado) $pendientes[$mes]++;
            if ($monto !== 0) {
                $detalles[$mes][] = [
                    'codigo' => $g['codigo'], 'nombre' => $g['nombre'],
                    'monto' => $monto / 100,
                    'anterior' => $g['anterior'] / 100,
                    'pagoInternet' => $g['internet'] / 100,
                    'procedencia' => $confirmado ? 'Cobros distintos conciliados' :
                        ($coinciden ? 'Coincidencia por revisar' :
                            ($g['anterior'] > 0 ? ($g['internet'] > 0 || $g['otros'] > 0 ? 'Historial y otros cobros' : 'Historial mensual') : 'Pago registrado')),
                    'notaConciliacion' => $confirmado ? $regla['nota'] : null,
                ];
            }
        }
        foreach ($detalles as &$lista) {
            usort($lista, static fn ($a, $b) => strcmp($a['codigo'], $b['codigo']));
        }
        unset($lista);
        return ['sumas' => $sumas, 'pendientes' => $pendientes, 'detalles' => $detalles];
    }
}
