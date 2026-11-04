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
    public function boot(): void { \App\Support\IspAccess::enforce('servicios.view'); }

    use WithPagination;

    // La lista es la pantalla de inicio; los formularios se abren solo al usarlos.
    public string $modoPantalla = 'lista';
    public string $buscar = '';
    public string $filtroEstado = 'todos';
    public string $filtroTipo = 'todos';
    public string $filtroLocalidad = 'todas';
    public bool $soloPorRevisar = false; // Conexiones con velocidad pendiente de registrar
    public string $clienteIdentificacion = ''; // Cabecera de edicion, solo lectura

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

    
    public function nuevo(): void
    {
        \App\Support\IspAccess::enforce('servicios.create');

        $this->cancelarEdicion();
        $this->cerrarCambioTarifa();
        $this->modoPantalla = 'formulario';
    }

    public function volverListado(): void
    {
        $this->cancelarEdicion();
        $this->cerrarCambioTarifa();
        $this->modoPantalla = 'lista';
    }

    public function updatedBuscar(): void { $this->resetPage(); }
    public function updatedFiltroEstado(): void { $this->resetPage(); }
    public function updatedFiltroTipo(): void { $this->resetPage(); }
    public function updatedFiltroLocalidad(): void { $this->resetPage(); }

    public function verConexiones(): void
    {
        $this->soloPorRevisar = false;
        $this->resetPage();
    }

    public function verPorRevisar(): void
    {
        $this->soloPorRevisar = true;
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->filtroEstado = 'todos';
        $this->filtroTipo = 'todos';
        $this->filtroLocalidad = 'todas';
        $this->resetPage();
    }

    public function abrirCambioTarifa(int $id): void
    {
        \App\Support\IspAccess::enforce('tarifas.manage');

        // Comprobar que el servicio existe
        $servicio = Servicio::findOrFail($id);

        // Seleccionar el servicio
        $this->servicioTarifaId = $servicio->id;
        $this->modoPantalla = 'tarifa';

        // Preparar el formulario
        $this->nuevoMonto = '';
        $this->nuevaVigencia = now()->toDateString();
        $this->motivoCambio = '';

        $this->resetValidation();
    }

    public function cerrarCambioTarifa(): void
    {
        $this->modoPantalla = 'lista';

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
        \App\Support\IspAccess::enforce('tarifas.manage');

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
        \App\Support\IspAccess::enforce('servicios.edit');

        $servicio = Servicio::with('cliente')->findOrFail($id);

        $this->clienteIdentificacion = trim(($servicio->cliente?->codigo ?? '') . ' - ' . ($servicio->cliente?->nombre ?? 'Cliente no disponible'));
        $this->servicioEditandoId = $servicio->id;
        $this->modoPantalla = 'formulario';

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
        \App\Support\IspAccess::enforce($this->servicioEditandoId === null ? 'servicios.create' : 'servicios.edit');

        if ($this->servicioTarifaId !== null) {
            $this->addError(
                'nuevoMonto',
                'Estás cambiando una tarifa. Utiliza el formulario de cambio de tarifa.'
            );

        return;
        }




        // En edición solo validamos y guardamos datos técnicos.
        // La tarifa inicial, el cliente titular y la fecha original NO son editables aquí.
        $reglas = [
            'tipo_conexion' => 'required|in:fibra,inalambrico',
            'nombre_plan' => 'nullable|string|max:100',
            'velocidad_mbps' => 'nullable|integer|min:1',
            'estado' => 'required|in:activo,suspendido',
            'direccion_instalacion' => 'nullable|string',
            'ip_asignada' => 'nullable|ip',
            'mac_equipo' => 'nullable|string|max:30',
            'observaciones' => 'nullable|string',
        ];

        if ($this->servicioEditandoId === null) {
            // Alta inicial: el modelo servicios exige una tarifa inicial y un cliente.
            // Se conserva la creación del historial de Tarifa existente.
            $reglas += [
                'cliente_id' => 'required|integer|exists:clientes,id',
                'tarifa_mensual' => 'required|numeric|min:0.01|max:99999999.99',
                'fecha_activacion' => 'nullable|date_format:Y-m-d',
            ];
        }

        $datos = $this->validate($reglas, [
            'cliente_id.required' => 'Selecciona un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'tipo_conexion.required' => 'Selecciona el tipo de conexión.',
            'tarifa_mensual.required' => 'Ingresa la tarifa inicial del servicio.',
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
            'clienteIdentificacion',
        ]);

        $this->fecha_activacion = '';
        $this->modoPantalla = 'lista';

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
            'clienteIdentificacion',
        ]);

        $this->fecha_activacion = '';
        $this->modoPantalla = 'lista';

        $this->resetValidation();
    }




    public function render()
    {
        $buscar = trim($this->buscar);

        $servicios = Servicio::query()
            ->with('cliente')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre_plan', 'like', "%{$buscar}%")
                        ->orWhere('ip_asignada', 'like', "%{$buscar}%")
                        ->orWhere('mac_equipo', 'like', "%{$buscar}%")
                        ->orWhereHas('cliente', function ($cliente) use ($buscar) {
                            $cliente->where('codigo', 'like', "%{$buscar}%")
                                ->orWhere('nombre', 'like', "%{$buscar}%")
                                ->orWhere('telefono', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($this->filtroEstado !== 'todos', fn ($q) =>
                $q->where('estado', $this->filtroEstado))
            ->when($this->filtroTipo !== 'todos', fn ($q) =>
                $q->where('tipo_conexion', $this->filtroTipo))
            ->when($this->soloPorRevisar, fn ($q) => $q->where(function ($v) {
                $v->whereNull('velocidad_mbps')->orWhere('velocidad_mbps', '<=', 0);
            }))
            ->when($this->filtroLocalidad !== 'todas', fn ($q) =>
                $q->whereHas('cliente', fn ($cliente) =>
                    $cliente->where('sector', $this->filtroLocalidad)))
            ->latest('id')
            ->paginate(15);

        return view('livewire.servicios.gestion', [
            'clientes' => $this->modoPantalla === 'formulario'
                ? Cliente::orderBy('nombre')->get(['id', 'codigo', 'nombre'])
                : collect(),
            'servicios' => $servicios,
            'localidades' => Cliente::query()->whereNotNull('sector')
                ->where('sector', '<>', '')
                ->distinct()->orderBy('sector')->pluck('sector'),
            'totalServicios' => Servicio::count(),
            'serviciosActivos' => Servicio::where('estado', 'activo')->count(),
            'serviciosSuspendidos' => Servicio::where('estado', 'suspendido')->count(),
            'serviciosPorRevisar' => Servicio::where(function ($q) {
                $q->whereNull('velocidad_mbps')->orWhere('velocidad_mbps', '<=', 0);
            })->count(),
        ]);
    }
}
