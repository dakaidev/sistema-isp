<?php

namespace Tests\Feature;

use App\Livewire\Servicios\Gestion;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ServicioTarifaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Esta prueba solo debe ejecutarse
        // en el entorno de testing.
        if (!app()->environment('testing')) {
            throw new \RuntimeException(
                'No se permite ejecutar fuera de testing.'
            );
        }

        // Forzar una base SQLite temporal en memoria.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        // Verificar la conexión ANTES de crear tablas.
        if (DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException(
                'La prueba no utiliza SQLite en memoria.'
            );
        }

        // Crear tablas únicamente en la base temporal.
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_registra_tarifa_inicial_automaticamente(): void
    {
        // Crear administrador ficticio.
        $usuario = User::factory()->create();

        // Crear cliente ficticio.
        $cliente = Cliente::create([
            'codigo' => 'TEST-AUTO-001',
            'nombre' => 'Cliente de prueba automática',
        ]);

        // Simular el formulario Livewire.
        Livewire::actingAs($usuario)
            ->test(Gestion::class)
            ->set('cliente_id', (string) $cliente->id)
            ->set('tipo_conexion', 'fibra')
            ->set('nombre_plan', 'Plan de prueba 100 Mbps')
            ->set('velocidad_mbps', '100')
            ->set('tarifa_mensual', '50.00')
            ->set('fecha_activacion', '2026-10-09')
            ->set('estado', 'activo')
            ->call('guardar')
            ->assertHasNoErrors();

        // Comprobar el servicio creado.
        $servicio = Servicio::firstOrFail();

        // Comprobar su primera tarifa.
        $tarifa = Tarifa::firstOrFail();

        $this->assertDatabaseCount('servicios', 1);
        $this->assertDatabaseCount('tarifas', 1);

        $this->assertEquals(
            $servicio->id,
            $tarifa->servicio_id
        );

        $this->assertEquals('50.00', $tarifa->monto);

        $this->assertEquals(
            '2026-10-09',
            $tarifa->vigente_desde->format('Y-m-d')
        );

        $this->assertEquals(
            'Tarifa inicial del servicio',
            $tarifa->motivo
        );

        $this->assertEquals(
            $usuario->id,
            $tarifa->usuario_id
        );
    }
}
