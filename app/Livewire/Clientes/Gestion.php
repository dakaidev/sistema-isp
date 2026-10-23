<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Tarifa;
use App\Support\PlanesInternet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Gestion extends Component
{
    use WithPagination;

    // Cliente que estamos editando
    public ?int $clienteEditandoId = null;

    // La lista y el formulario se muestran en paginas distintas.
    public string $modoPantalla = 'lista';
    public bool $mostrarFiltrosAvanzados = false;

    // Formulario
    public string $codigo = '';
    public string $nombre = '';
    public string $documento = '';
    public string $telefono = '';
    public string $sector = '';
    // Upina y Calasuca son sedes con servidores independientes.
    public string $sede = '';
    // Un solo campo visual, dos columnas GPS conservadas en MySQL.
    public string $ubicacionGps = '';
    public string $direccion = '';
    public string $fechaInstalacion = '';
    public string $fechaBaja = '';
    public string $observaciones = '';

    // Perfil unificado: la facturacion usa siempre isp_configuracion_mensual.
    public ?int $perfilClienteId = null;
    public string $perfilClienteNombre = '';
    public string $perfilTarifa = '';
    public string $perfilInicio = '';
    public bool $perfilHabilitado = false;
    public string $perfilSede = '';

    // Informacion tecnica opcional: NO habilita facturacion por si sola.
    public ?int $perfilServicioId = null;
    public int $perfilCantidadServicios = 0;
    public string $perfilTipoConexion = 'fibra';
    public string $perfilPlan = '';
    public string $perfilPlanElegido = 'personalizado';
    public string $perfilVelocidad = '';
    public string $perfilFechaActivacion = '';
    public string $perfilEstado = 'activo';

    public function mount(): void
    {
        if (request()->routeIs('clientes.nuevo')) {
            $this->modoPantalla = 'formulario';
            return;
        }

        if (request()->routeIs('clientes.editar')) {
            $this->modoPantalla = 'formulario';
            $cliente = request()->route('cliente');
            abort_unless(is_scalar($cliente) && ctype_digit((string) $cliente), 404);
            $this->editar((int) $cliente);
            return;
        }

        $id = request()->query('cliente');
        if (is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0 && Cliente::whereKey((int) $id)->exists()) {
            $this->abrirPerfil((int) $id);
        }
    }

    // Buscador
    public string $buscar = '';
    public string $filtroSede = 'todos';

    // Filtros combinables: sede, estado administrativo, facturacion y plan.
    // Cambiar un filtro no altera los datos ni aplica cortes al servidor.
    public string $filtroEstadoConexion = 'todos';
    public string $filtroFacturacion = 'todos';
    public string $filtroPlan = 'todos';
    public string $filtroDeuda = 'todos';
    public string $filtroRevision = 'todos';

    public function updatedFiltroSede(): void { $this->resetPage(); }
    public function updatedFiltroEstadoConexion(): void { $this->resetPage(); }
    public function updatedFiltroFacturacion(): void { $this->resetPage(); }
    public function updatedFiltroPlan(): void { $this->resetPage(); }
    public function updatedFiltroDeuda(): void { $this->resetPage(); }
    public function updatedFiltroRevision(): void { $this->resetPage(); }

    public function alternarFiltrosAvanzados(): void
    {
        $this->mostrarFiltrosAvanzados = ! $this->mostrarFiltrosAvanzados;
    }
    public function updatedBuscar(): void { $this->resetPage(); }

    public function limpiarFiltros(): void
    {
        $this->filtroSede = 'todos';
        $this->filtroEstadoConexion = 'todos';
        $this->filtroFacturacion = 'todos';
        $this->filtroPlan = 'todos';
        $this->filtroDeuda = 'todos';
        $this->filtroRevision = 'todos';
        $this->buscar = '';
        $this->resetPage();
    }

    // Cargar los datos de un cliente en el formulario
    public function editar(int $id): void
    {
        $cliente = Cliente::findOrFail($id);

        $this->clienteEditandoId = $cliente->id;
        $this->codigo = $cliente->codigo;
        $this->nombre = $cliente->nombre;
        $this->documento = $cliente->documento ?? '';
        $this->telefono = $cliente->telefono ?? '';
        $this->sector = $cliente->sector ?? '';
        $this->sede = $cliente->sede ?? '';
        $this->ubicacionGps = $cliente->latitud !== null && $cliente->longitud !== null
            ? (string) $cliente->latitud . ', ' . (string) $cliente->longitud
            : '';
        $this->direccion = $cliente->direccion ?? '';
        $this->fechaInstalacion = $cliente->fecha_instalacion?->format('Y-m-d') ?? '';
        $this->fechaBaja = $cliente->fecha_baja?->format('Y-m-d') ?? '';
        $this->observaciones = $cliente->observaciones ?? '';

        $this->resetValidation();
    }

    // Guardar un cliente nuevo o actualizar uno existente
    public function guardar(): void
    {
        $this->codigo = strtoupper(trim($this->codigo));
        $this->nombre = trim($this->nombre);
        $this->ubicacionGps = trim($this->ubicacionGps);

        $cliente = $this->clienteEditandoId !== null
            ? Cliente::findOrFail($this->clienteEditandoId)
            : null;

        // Validar que el código no pertenezca a otro cliente
        $codigoUnico = Rule::unique('clientes', 'codigo');

        if ($cliente !== null) {
            $codigoUnico->ignore($cliente);
        }

        $datos = $this->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                $codigoUnico,
            ],
            'nombre' => 'required|string|max:160',
            'documento' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            // El sector antiguo se conserva en MySQL sin reescribirlo.
            'sede' => [$cliente === null ? 'required' : 'nullable', Rule::in(['upina', 'calasuca'])],
            'ubicacionGps' => ['nullable', 'string', 'max:80'],
            'direccion' => 'nullable|string',
            'fechaInstalacion' => ['nullable', 'date_format:Y-m-d'],
            'fechaBaja' => ['nullable', 'date_format:Y-m-d'],
            'observaciones' => 'nullable|string',
        ], [
            'codigo.required' => 'Ingresa el código del cliente.',
            'codigo.unique' => 'Este código ya está registrado.',
            'nombre.required' => 'Ingresa el nombre del cliente.',
            'sede.required' => 'Selecciona Upina o Calasuca.',
            'sede.in' => 'Sede no válida.',
            'ubicacionGps.max' => 'La ubicacion GPS es demasiado larga.',
        ]);

        // Fechas historicas del cliente. NO alteran facturacion ni bajas tecnicas.
        $instalacion = $datos['fechaInstalacion'] ?? '';
        $baja = $datos['fechaBaja'] ?? '';
        if ($instalacion !== '' && $baja !== '' && $baja < $instalacion) {
            throw ValidationException::withMessages([
                'fechaBaja' => 'La fecha de baja no puede ser anterior a la instalacion.',
            ]);
        }
        unset($datos['fechaInstalacion'], $datos['fechaBaja']);
        $datos['fecha_instalacion'] = $instalacion !== '' ? $instalacion : null;
        $datos['fecha_baja'] = $baja !== '' ? $baja : null;

        // Las coordenadas ingresadas en una sola casilla se conservan en dos
        // columnas compatibles con las fichas y el enlace a Google Maps.
        $datos['sede'] = $datos['sede'] ?: null;
        $gps = $datos['ubicacionGps'] ?? '';
        unset($datos['ubicacionGps']);
        if ($gps === '') {
            $datos['latitud'] = null;
            $datos['longitud'] = null;
        } else {
            // Formato exacto de Google Maps: latitud, longitud.
            // El decimal se escribe con punto; la coma separa ambos valores.
            if (!preg_match('/\A([+-]?\d{1,2}(?:\.\d{1,7})?)\s*,\s*([+-]?\d{1,3}(?:\.\d{1,7})?)\z/', $gps, $partes)
                || (float) ($partes[1] ?? 0) < -90 || (float) ($partes[1] ?? 0) > 90
                || (float) ($partes[2] ?? 0) < -180 || (float) ($partes[2] ?? 0) > 180) {
                throw ValidationException::withMessages([
                    'ubicacionGps' => 'Pega dos coordenadas validas, por ejemplo: -13.8824429, -70.0870094',
                ]);
            }
            $datos['latitud'] = $partes[1];
            $datos['longitud'] = $partes[2];
        }

        if ($cliente !== null) {
            $cliente->update($datos);
            $mensaje = 'Cliente actualizado correctamente.';
        } else {
            $cliente = Cliente::create($datos);
            $mensaje = 'Cliente registrado. Ahora puedes configurar su facturacion.';
        }

        if ($this->modoPantalla === 'formulario') {
            session()->flash('mensaje', $mensaje);
            $this->redirect(route('clientes.index', ['cliente' => $cliente->id]), navigate: true);
            return;
        }

        $this->cancelarEdicion();
        $this->resetPage();
        $this->abrirPerfil((int) $cliente->id);

        session()->flash('mensaje', $mensaje);
    }

    // Volver al formulario de registro nuevo
    public function cancelarEdicion(): void
    {
        $this->reset([
            'clienteEditandoId',
            'codigo',
            'nombre',
            'documento',
            'telefono',
            'sector',
            'sede',
            'ubicacionGps',
            'direccion',
            'fechaInstalacion',
            'fechaBaja',
            'observaciones',
        ]);

        $this->resetValidation();
    }

    // Cargar una sola vez facturacion y el servicio tecnico, si existe.
    public function abrirPerfil(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $cfg = DB::table('isp_configuracion_mensual')->where('cliente_id', $id)->first();
        $servicios = Servicio::query()->where('cliente_id', $id)->orderByDesc('id')->get();
        $servicio = $servicios->first();

        $this->perfilClienteId = $id;
        $this->perfilClienteNombre = $cliente->codigo . ' - ' . $cliente->nombre;
        $this->perfilTarifa = $cfg && $cfg->tarifa_mensual !== null ? (string) $cfg->tarifa_mensual : '';
        $this->perfilInicio = $cfg?->inicio_cobro ?: today('America/Lima')->startOfMonth()->toDateString();
        $this->perfilHabilitado = $cfg ? (bool) $cfg->habilitado : false;
        $this->perfilSede = $cliente->sede ?? '';

        $this->perfilCantidadServicios = $servicios->count();
        $this->perfilServicioId = $servicio?->id;
        $this->perfilTipoConexion = $servicio?->tipo_conexion ?: 'fibra';
        $this->perfilPlan = $servicio?->nombre_plan ?? '';
        $this->perfilVelocidad = $servicio?->velocidad_mbps !== null ? (string) $servicio->velocidad_mbps : '';
        $this->perfilPlanElegido = PlanesInternet::identificar($this->perfilPlan, $servicio?->velocidad_mbps);
        // Fecha técnica real: no inventarla al abrir una conexión nueva.
        $this->perfilFechaActivacion = $servicio?->fecha_activacion?->format('Y-m-d') ?? '';
        $this->perfilEstado = $servicio?->estado ?: 'activo';
        $this->resetValidation();
    }

    public function cerrarPerfil(): void
    {
        $this->reset([
            'perfilClienteId', 'perfilClienteNombre', 'perfilTarifa', 'perfilInicio',
            'perfilHabilitado', 'perfilSede', 'perfilServicioId', 'perfilCantidadServicios',
            'perfilTipoConexion', 'perfilPlan', 'perfilPlanElegido', 'perfilVelocidad',
            'perfilFechaActivacion', 'perfilEstado',
        ]);
        $this->resetValidation();
    }

    // La sede indica a qué servidor independiente pertenece el cliente.
    // No genera servicios, mensualidades, cargos ni pagos.
    public function guardarSede(): void
    {
        if ($this->perfilClienteId === null) abort(400);

        $datos = $this->validate([
            'perfilSede' => ['required', Rule::in(['upina', 'calasuca'])],
        ], [
            'perfilSede.required' => 'Elige la sede real del cliente.',
            'perfilSede.in' => 'Solo puedes elegir Upina o Calasuca.',
        ]);

        Cliente::whereKey($this->perfilClienteId)->firstOrFail()
            ->update(['sede' => $datos['perfilSede']]);

        $id = $this->perfilClienteId;
        $this->abrirPerfil($id);
        session()->flash('mensaje', 'Sede guardada. No se modificaron tarifas, servicios, deudas ni pagos.');
    }

    public function guardarFacturacion(): void
    {
        if ($this->perfilClienteId === null) abort(400);

        $datos = $this->validate([
            'perfilTarifa' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
            'perfilInicio' => ['required', 'date_format:Y-m-d'],
            'perfilHabilitado' => ['boolean'],
        ]);

        DB::transaction(function () use ($datos) {
            Cliente::whereKey($this->perfilClienteId)->lockForUpdate()->firstOrFail();
            $cfg = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->perfilClienteId)->first();
            $valores = [
                'tarifa_mensual' => number_format((float) $datos['perfilTarifa'], 2, '.', ''),
                'inicio_cobro' => $datos['perfilInicio'],
                'habilitado' => (bool) $datos['perfilHabilitado'],
                'updated_at' => now(),
            ];
            if ($cfg) {
                DB::table('isp_configuracion_mensual')->where('cliente_id', $this->perfilClienteId)->update($valores);
            } else {
                DB::table('isp_configuracion_mensual')->insert(['cliente_id' => $this->perfilClienteId, 'created_at' => now()] + $valores);
            }
        }, 3);

        $id = $this->perfilClienteId;
        $this->abrirPerfil($id);
        session()->flash('mensaje', 'Facturacion guardada. No se modificaron cargos ya emitidos ni pagos.');
    }

    // Elegir un plan prepara el nombre y velocidad tecnicos, sin cobrar ni cambiar tarifas.
    public function updatedPerfilPlanElegido(): void
    {
        $plan = PlanesInternet::CATALOGO[$this->perfilPlanElegido] ?? null;
        if ($plan !== null) {
            $this->perfilPlan = $plan['nombre'];
            $this->perfilVelocidad = (string) $plan['velocidad'];
        }
    }

    // Copia solamente el precio de referencia al campo de facturacion.
    // El usuario debe pulsar "Guardar facturacion" para persistirlo.
    public function proponerTarifaPlan(): void
    {
        if ($this->perfilClienteId === null) abort(400);
        $plan = PlanesInternet::CATALOGO[$this->perfilPlanElegido] ?? null;
        if ($plan === null) {
            throw ValidationException::withMessages(['perfilPlanElegido' => 'Elige uno de los planes sugeridos.']);
        }
        $this->perfilTarifa = number_format($plan['precio'], 2, '.', '');
    }

    public function guardarConexion(): void
    {
        if ($this->perfilClienteId === null) abort(400);
        if ($this->perfilCantidadServicios > 1) {
            throw ValidationException::withMessages(['perfilTipoConexion' => 'Este cliente tiene varias conexiones. Revisalas individualmente en Servicios de Internet.']);
        }

        $reglas = [
            'perfilTipoConexion' => ['required', 'in:fibra,inalambrico'],
            'perfilPlanElegido' => ['required', Rule::in(array_merge(['personalizado'], array_keys(PlanesInternet::CATALOGO)))],
            'perfilPlan' => ['nullable', 'string', 'max:100'],
            'perfilVelocidad' => ['nullable', 'integer', 'between:1,100000'],
            'perfilEstado' => ['required', 'in:activo,suspendido'],
        ];
        // Fecha completamente opcional, incluso al registrar una conexion nueva.
        $reglas['perfilFechaActivacion'] = ['nullable', 'date_format:Y-m-d'];
        $datos = $this->validate($reglas);
        $planElegido = PlanesInternet::CATALOGO[$datos['perfilPlanElegido']] ?? null;
        if ($planElegido !== null) {
            $datos['perfilPlan'] = $planElegido['nombre'];
            $datos['perfilVelocidad'] = (string) $planElegido['velocidad'];
        }

        DB::transaction(function () use ($datos) {
            Cliente::whereKey($this->perfilClienteId)->lockForUpdate()->firstOrFail();
            $servicios = Servicio::where('cliente_id', $this->perfilClienteId)->lockForUpdate()->get();
            if ($servicios->count() > 1) {
                throw ValidationException::withMessages(['perfilTipoConexion' => 'Se detectaron varias conexiones: no se modifico ninguna.']);
            }

            $campos = [
                'tipo_conexion' => $datos['perfilTipoConexion'],
                'nombre_plan' => trim($datos['perfilPlan'] ?? '') ?: null,
                'velocidad_mbps' => ($datos['perfilVelocidad'] ?? '') !== '' ? (int) $datos['perfilVelocidad'] : null,
                'estado' => $datos['perfilEstado'],
            ];
            $servicio = $servicios->first();
            if ($servicio) {
                $servicio->update($campos);
            } else {
                $cfg = DB::table('isp_configuracion_mensual')->where('cliente_id', $this->perfilClienteId)->first();
                if (!$cfg || (float) $cfg->tarifa_mensual <= 0) {
                    throw ValidationException::withMessages(['perfilTipoConexion' => 'Primero guarda la tarifa de facturacion de este cliente.']);
                }
                $servicio = Servicio::create($campos + [
                    'cliente_id' => $this->perfilClienteId,
                    'tarifa_mensual' => $cfg->tarifa_mensual,
                    'fecha_activacion' => $datos['perfilFechaActivacion'] ?: null,
                ]);
                Tarifa::create([
                    'servicio_id' => $servicio->id,
                    'monto' => $cfg->tarifa_mensual,
                    'vigente_desde' => $datos['perfilFechaActivacion'] ?: today('America/Lima')->toDateString(),
                    'motivo' => $datos['perfilFechaActivacion'] ? 'Tarifa inicial al registrar la conexion tecnica' : 'Alta administrativa de tarifa tecnica, sin fecha de activacion confirmada',
                    'usuario_id' => Auth::id(),
                ]);
            }
        }, 3);

        $id = $this->perfilClienteId;
        $this->abrirPerfil($id);
        session()->flash('mensaje', 'Conexion tecnica guardada. No se generaron mensualidades ni se registraron pagos.');
    }

    // Listado y búsqueda
    public function render()
    {
        // El formulario tiene pagina independiente: no consulta la lista ni calcula estadisticas.
        if ($this->modoPantalla === 'formulario') {
            return view('livewire.clientes.gestion');
        }

        // Los saldos son cargos confirmados menos pagos efectivamente aplicados.
        // Las anotaciones historicas sin cargo no generan deuda aqui.
        $aplicaciones = DB::table('isp_aplicaciones_pago')
            ->select('cargo_id')
            ->selectRaw('SUM(monto) AS total_aplicado')
            ->groupBy('cargo_id');

        $existeDeuda = function ($query) use ($aplicaciones) {
            $query->selectRaw('1')
                ->from('isp_cargos as cargo_deuda')
                ->leftJoinSub(clone $aplicaciones, 'aplicacion_deuda', 'aplicacion_deuda.cargo_id', '=', 'cargo_deuda.id')
                ->whereColumn('cargo_deuda.cliente_id', 'clientes.id')
                ->whereRaw('cargo_deuda.monto > COALESCE(aplicacion_deuda.total_aplicado, 0)');
        };

        // La tarifa real para facturar procede SOLO de isp_configuracion_mensual.
        // Servicio tecnico opcional: elegir el ultimo registro, sin duplicar filas.
        $ultimoServicio = Servicio::query()
            ->selectRaw('cliente_id, MAX(id) as ultimo_id')
            ->groupBy('cliente_id');

        $clientes = Cliente::query()
            ->leftJoin('isp_configuracion_mensual as cfg', 'cfg.cliente_id', '=', 'clientes.id')
            ->leftJoinSub($ultimoServicio, 'servicio_ultimo', 'servicio_ultimo.cliente_id', '=', 'clientes.id')
            ->leftJoin('servicios as svc', 'svc.id', '=', 'servicio_ultimo.ultimo_id')
            ->select([
                'clientes.*',
                'cfg.tarifa_mensual as tarifa_cobro',
                'cfg.inicio_cobro as fecha_cobro',
                'cfg.habilitado as cobro_habilitado',
                'svc.id as servicio_tecnico_id',
                'svc.tipo_conexion as tipo_tecnico',
                'svc.estado as estado_tecnico',
                'svc.nombre_plan as plan_tecnico',
                'svc.velocidad_mbps as velocidad_tecnica',
            ])
            ->when($this->filtroSede === 'upina' || $this->filtroSede === 'calasuca',
                fn ($q) => $q->where('clientes.sede', $this->filtroSede))
            ->when($this->filtroSede === 'sin_asignar',
                fn ($q) => $q->where(function ($sq) {
                    $sq->whereNull('clientes.sede')->orWhere('clientes.sede', '');
                }))
            ->when($this->filtroEstadoConexion === 'activo',
                fn ($q) => $q->whereNull('clientes.fecha_baja')->where('svc.estado', 'activo'))
            ->when($this->filtroEstadoConexion === 'suspendido',
                fn ($q) => $q->whereNull('clientes.fecha_baja')->where('svc.estado', 'suspendido'))
            ->when($this->filtroEstadoConexion === 'baja',
                fn ($q) => $q->whereNotNull('clientes.fecha_baja'))
            ->when($this->filtroEstadoConexion === 'sin_registro',
                fn ($q) => $q->whereNull('clientes.fecha_baja')->whereNull('svc.id'))
            ->when($this->filtroFacturacion === 'habilitada',
                fn ($q) => $q->where('cfg.habilitado', 1))
            ->when($this->filtroFacturacion === 'deshabilitada',
                fn ($q) => $q->where(fn ($sq) => $sq->whereNull('cfg.id')->orWhere('cfg.habilitado', 0)))
            ->when($this->filtroDeuda === 'con_deuda', fn ($q) => $q->whereExists($existeDeuda))
            ->when($this->filtroDeuda === 'sin_deuda', fn ($q) => $q->whereNotExists($existeDeuda))
            ->when($this->filtroRevision === 'suspendido_facturando',
                fn ($q) => $q->whereNull('clientes.fecha_baja')->where('svc.estado', 'suspendido')->where('cfg.habilitado', 1))
            ->when($this->filtroRevision === 'activo_sin_facturar',
                fn ($q) => $q->whereNull('clientes.fecha_baja')->where('svc.estado', 'activo')
                    ->where(fn ($sub) => $sub->whereNull('cfg.id')->orWhere('cfg.habilitado', 0)))
            ->when($this->filtroPlan === 'sin_plan',
                fn ($q) => $q->where(fn ($sq) => $sq->whereNull('svc.nombre_plan')->orWhere('svc.nombre_plan', '')))
            ->when(in_array($this->filtroPlan, array_keys(PlanesInternet::CATALOGO), true),
                fn ($q) => $q->where('svc.nombre_plan', PlanesInternet::CATALOGO[$this->filtroPlan]['nombre']))
            ->when(trim($this->buscar) !== '', function ($query) {
                $texto = trim($this->buscar);
                $query->where(function ($q) use ($texto) {
                    $q->where('clientes.codigo', 'like', "%{$texto}%")
                      ->orWhere('clientes.nombre', 'like', "%{$texto}%")
                      ->orWhere('clientes.telefono', 'like', "%{$texto}%")
                      ->orWhere('clientes.direccion', 'like', "%{$texto}%")
                      ->orWhere('clientes.documento', 'like', "%{$texto}%");
                });
            })
            ->orderByDesc('clientes.id')
            ->paginate(15);

        // Una unica consulta agregada para los clientes de la pagina (evita N+1).
        $idsPagina = $clientes->getCollection()->pluck('id')->all();
        $deudasPagina = empty($idsPagina) ? collect() : DB::table('isp_cargos as cargo')
            ->leftJoinSub(clone $aplicaciones, 'aplicacion', 'aplicacion.cargo_id', '=', 'cargo.id')
            ->whereIn('cargo.cliente_id', $idsPagina)
            ->select('cargo.cliente_id')
            ->selectRaw('SUM(GREATEST(cargo.monto - COALESCE(aplicacion.total_aplicado, 0), 0)) AS pendiente')
            ->groupBy('cargo.cliente_id')
            ->pluck('pendiente', 'cargo.cliente_id');

        $clientes->getCollection()->each(function ($cliente) use ($deudasPagina) {
            $cliente->setAttribute('deuda_confirmada', (float) ($deudasPagina[$cliente->id] ?? 0));
        });

        // Conteos globales, no dependen de los filtros ni de la paginacion.
        // Si una baja fue anotada, prevalece sobre el estado tecnico guardado.
        $estados = DB::table('clientes')
            ->leftJoinSub(clone $ultimoServicio, 'ultimo_estado', 'ultimo_estado.cliente_id', '=', 'clientes.id')
            ->leftJoin('servicios as estado_svc', 'estado_svc.id', '=', 'ultimo_estado.ultimo_id')
            ->selectRaw("SUM(CASE WHEN clientes.fecha_baja IS NULL AND estado_svc.estado = 'activo' THEN 1 ELSE 0 END) as activos")
            ->selectRaw("SUM(CASE WHEN clientes.fecha_baja IS NULL AND estado_svc.estado = 'suspendido' THEN 1 ELSE 0 END) as suspendidos")
            ->selectRaw("SUM(CASE WHEN clientes.fecha_baja IS NOT NULL THEN 1 ELSE 0 END) as bajas")
            ->selectRaw("SUM(CASE WHEN clientes.fecha_baja IS NULL AND estado_svc.id IS NULL THEN 1 ELSE 0 END) as sin_registro")
            ->first();

        return view('livewire.clientes.gestion', [
            'clientes' => $clientes,
            'totalClientes' => Cliente::count(),
            'totalUpina' => Cliente::where('sede', 'upina')->count(),
            'totalCalasuca' => Cliente::where('sede', 'calasuca')->count(),
            'totalSinSede' => Cliente::whereNull('sede')->orWhere('sede', '')->count(),
            'totalFacturacion' => DB::table('isp_configuracion_mensual')->where('habilitado', true)->count(),
            'totalConexion' => DB::table('servicios')->distinct('cliente_id')->count('cliente_id'),
            'totalActivos' => (int) ($estados->activos ?? 0),
            'totalSuspendidos' => (int) ($estados->suspendidos ?? 0),
            'totalBajas' => (int) ($estados->bajas ?? 0),
            'totalSinRegistro' => (int) ($estados->sin_registro ?? 0),
        ]);
    }
}
