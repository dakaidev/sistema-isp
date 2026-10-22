<?php

namespace App\Livewire\Servicios;

use App\Models\Cliente;
use App\Models\Servicio;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tarifa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Gestion extends Component
{
    use WithPagination;

    public ?int $servicioEditandoId = null;
   
    // Formulario para cambiar tarifas
    public ?int $servicioTarifaId = null;

    public string $nuevoMonto = '';
    public string $nuevaVigencia = '';
    public string $motivoCambio = '';

    public string $cliente_id = '';
    public string $tipo_conexion = 'fibra';
    public string $nombre_plan = '';
    public string $velocidad_mbps = '';
    public string $tarifa_mensual = '';
    public string $fecha_activacion = '';
    public string $estado = 'activo';
    public string $direccion_instalacion = '';
    public string $ip_asignada = '';
    public string $mac_equipo = '';
    public string $observaciones = '';

    public function mount(): void
    {
        $this->fecha_activacion = '';
    }

    
    public function abrirCambioTarifa(int $id): void
    {
        // Comprobar que el servicio existe
        $servicio = Servicio::findOrFail($id);

        // Seleccionar el servicio
        $this->servicioTarifaId = $servicio->id;

        // Preparar el formulario
        $this->nuevoMonto = '';
        $this->nuevaVigencia = now()->toDateString();
        $this->motivoCambio = '';

        $this->resetValidation();
    }

    public function cerrarCambioTarifa(): void
    {
        $this->reset([
            'servicioTarifaId',
            'nuevoMonto',
            'nuevaVigencia',
            'motivoCambio',
        ]);

        $this->resetValidation();
    }

    
    public function guardarCambioTarifa(): void
    {
        if ($this->servicioTarifaId === null) {
            $this->addError(
                'nuevoMonto',
                'Primero selecciona un servicio.'
            );
            return;
        }

        $this->motivoCambio = trim($this->motivoCambio);

        // Validar el nuevo precio, la fecha y el motivo
        $datos = $this->validate([
            'nuevoMonto' => [
                'required',
                'numeric',
                'decimal:0,2',
                'between:0.01,10000',
            ],
            'nuevaVigencia' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'motivoCambio' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
        ]);

        DB::transaction(function () use ($datos) {

            // Bloquear el servicio mientras guardamos
            $servicio = Servicio::whereKey(
                $this->servicioTarifaId
            )
                ->lockForUpdate()
                ->firstOrFail();

            // Buscar su última tarifa registrada
            $ultimaTarifa = Tarifa::where(
                'servicio_id',
                $servicio->id
            )
                ->orderByDesc('vigente_desde')
                ->orderByDesc('id')
                ->first();

            if ($ultimaTarifa === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nuevoMonto' => 'Este servicio no tiene una tarifa inicial.',
                ]);
            }

            // Evitar dos tarifas con la misma fecha o
            // fechas anteriores al último cambio
            if (
                $datos['nuevaVigencia'] <=
                $ultimaTarifa->vigente_desde->format('Y-m-d')
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nuevaVigencia' =>
                        'La fecha debe ser posterior al último cambio: '
                        . $ultimaTarifa->vigente_desde->format('d/m/Y'),
                ]);
            }

            // Evitar registrar otra vez el mismo precio
            if (
                number_format((float) $datos['nuevoMonto'], 2, '.', '')
                ===
                number_format((float) $ultimaTarifa->monto, 2, '.', '')
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nuevoMonto' => 'La nueva tarifa debe ser diferente.',
                ]);
            }

            // Guardar un nuevo registro en el historial
            Tarifa::create([
                'servicio_id' => $servicio->id,
                'monto' => $datos['nuevoMonto'],
                'vigente_desde' => $datos['nuevaVigencia'],
                'motivo' => $datos['motivoCambio'],
                'usuario_id' => Auth::id(),
            ]);

            // Si entra en vigor hoy, actualizar
            // también el precio actual del servicio
            if ($datos['nuevaVigencia'] === now()->toDateString()) {
                $servicio->update([
                    'tarifa_mensual' => $datos['nuevoMonto'],
                ]);
            }
        });

        $this->cerrarCambioTarifa();

        session()->flash(
            'mensaje',
            'Cambio de tarifa registrado correctamente.'
        );
    }



    
    public function editar(int $id): void
    {
        $servicio = Servicio::findOrFail($id);

        $this->servicioEditandoId = $servicio->id;

        $this->cliente_id = (string) $servicio->cliente_id;
        $this->tipo_conexion = $servicio->tipo_conexion;
        $this->nombre_plan = $servicio->nombre_plan ?? '';

        $this->velocidad_mbps =
            (string) ($servicio->velocidad_mbps ?? '');

        $this->tarifa_mensual =
            (string) $servicio->tarifa_mensual;

        $this->fecha_activacion =
            $servicio->fecha_activacion?->format('Y-m-d') ?? '';

        $this->estado = $servicio->estado;

        $this->direccion_instalacion =
            $servicio->direccion_instalacion ?? '';

        $this->ip_asignada = $servicio->ip_asignada ?? '';
        $this->mac_equipo = $servicio->mac_equipo ?? '';
        $this->observaciones = $servicio->observaciones ?? '';

        $this->resetValidation();
    }



    public function guardar(): void
    {
        if ($this->servicioTarifaId !== null) {
            $this->addError(
                'nuevoMonto',
                'Estás cambiando una tarifa. Utiliza el formulario de cambio de tarifa.'
            );

        return;
        }




        $datos = $this->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'tipo_conexion' => 'required|in:fibra,inalambrico',
            'nombre_plan' => 'nullable|string|max:100',
            'velocidad_mbps' => 'nullable|integer|min:1',
            'tarifa_mensual' => 'required|numeric|min:0.01|max:99999999.99',
            'fecha_activacion' => 'nullable|date_format:Y-m-d',
            'estado' => 'required|in:activo,suspendido',
            'direccion_instalacion' => 'nullable|string',
            'ip_asignada' => 'nullable|ip',
            'mac_equipo' => 'nullable|string|max:30',
            'observaciones' => 'nullable|string',
        ], [
            'cliente_id.required' => 'Selecciona un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'tipo_conexion.required' => 'Selecciona el tipo de conexión.',
            'tarifa_mensual.required' => 'Ingresa la mensualidad.',
            'tarifa_mensual.numeric' => 'La mensualidad debe ser un número.',
            'ip_asignada.ip' => 'Ingresa una dirección IP válida.',
        ]);


        
        // Convertir campos opcionales vacíos a NULL
        foreach ([
            'nombre_plan',
            'velocidad_mbps',
            'fecha_activacion',
            'direccion_instalacion',
            'ip_asignada',
            'mac_equipo',
            'observaciones',
        ] as $campo) {

            if (($datos[$campo] ?? null) === '') {
                $datos[$campo] = null;
            }

        }


        
        if ($this->servicioEditandoId !== null) {

            // Buscar el servicio que estamos editando
            $servicio = Servicio::findOrFail(
                $this->servicioEditandoId
            );

            // Estos datos se modificarán mediante
            // procesos especiales con historial
            unset(
                $datos['cliente_id'],
                $datos['tarifa_mensual'],
                $datos['fecha_activacion']
            );

            // Actualizar sin crear otro registro
            $servicio->update($datos);

            $mensaje = '¡Servicio actualizado correctamente!';

        
        } else {

            DB::transaction(function () use ($datos) {

                // 1. Crear el servicio
                $servicio = Servicio::create($datos);

                // 2. Registrar su primera tarifa
                Tarifa::create([
                    'servicio_id' => $servicio->id,
                    'monto' => $servicio->tarifa_mensual,
                    'vigente_desde' => $servicio->fecha_activacion?->toDateString() ?? today('America/Lima')->toDateString(),
                    'motivo' => $servicio->fecha_activacion ? 'Tarifa inicial del servicio' : 'Alta administrativa de tarifa tecnica, sin fecha de activacion confirmada',
                    'usuario_id' => Auth::id(),
                ]);

            });

            $mensaje = '¡Servicio y tarifa inicial registrados correctamente!';
        }


        $this->reset([
            'servicioEditandoId',
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
        ]);

        $this->fecha_activacion = '';

        $this->resetPage();

        
        session()->flash('mensaje', $mensaje);

    }


    public function cancelarEdicion(): void
    {
        $this->reset([
            'servicioEditandoId',
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
        ]);

        $this->fecha_activacion = '';

        $this->resetValidation();
    }




    public function render()
    {
        return view('livewire.servicios.gestion', [
            'clientes' => Cliente::orderBy('nombre')->get([
                'id',
                'codigo',
                'nombre',
            ]),

            'servicios' => Servicio::with('cliente')
                ->latest('id')
                ->paginate(10),

            'totalServicios' => Servicio::count(),

            'serviciosActivos' => Servicio::where(
                'estado',
                'activo'
            )->count(),
        ]);
    }
}
