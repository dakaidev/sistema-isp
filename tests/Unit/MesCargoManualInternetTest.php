<?php

namespace Tests\Unit;

use App\Livewire\Cobranza\FichaCliente;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MesCargoManualInternetTest extends TestCase
{
    public function test_solo_imputa_mes_de_cargos_manuales_inequivocos(): void
    {
        $ficha = new FichaCliente;
        $metodo = new ReflectionMethod(FichaCliente::class, 'mesCargoManualInternet');

        $casos = [
            ['deuda setiembre', '2026-09-01', '2026-09-01'],
            ['DEUDA SETIEMBRE', '2026-09-07', '2026-09-01'],
            ['SETTIEMBRE', '2026-09-01', '2026-09-01'],
            ['DEUDA AGOSTO', '2026-08-07', '2026-08-01'],
            ['JULIO DEUDA', '2026-07-07', '2026-07-01'],
            ['Internet Noviembre 2026', '2026-11-10', '2026-11-01'],
            ['debe muchos mese menos mayo', '2026-10-01', null],
            ['deuda agosto y setiembre', '2026-09-01', null],
            ['saldo de meses anteriores', '2026-10-01', null],
            ['deuda setiembre', '2026-10-01', null],
            ['deuda setiembre 2025', '2026-09-01', null],
            ['cuota sin pagar agosto', '2026-08-01', null],
            ['saldo excel verificado', '2026-10-01', null],
            ['deuda octubre', null, null],
        ];

        foreach ($casos as [$descripcion, $vencimiento, $esperado]) {
            $this->assertSame(
                $esperado,
                $metodo->invoke($ficha, $descripcion, $vencimiento),
                'No debe atribuir un mes equivocado al cargo: '.$descripcion
            );
        }
    }
}
