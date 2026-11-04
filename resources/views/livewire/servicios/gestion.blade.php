<div class="mx-auto w-full max-w-7xl space-y-5 pb-12">
    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-semibold text-emerald-700 dark:text-emerald-300" role="status">
            {{ session('mensaje') }}
        </div>
    @endif

    @if ($modoPantalla === 'formulario')
        <header class="rounded-2xl bg-gradient-to-r from-slate-950 to-blue-900 px-5 py-6 text-white sm:px-7">
            <button type="button" wire:click="volverListado" class="text-xs font-semibold text-blue-200 hover:text-white">← Volver a conexiones</button>
            <h1 class="mt-2 text-2xl font-bold">{{ $servicioEditandoId !== null ? 'Editar conexión técnica' : 'Registrar conexión de Internet' }}</h1>
            <p class="mt-1 text-sm text-blue-100/80">
                {{ $servicioEditandoId !== null ? 'Modifica los datos de la instalación sin alterar el historial económico.' : 'Asigna un servicio a un cliente existente y configura sus datos técnicos.' }}
            </p>
        </header>

        <section x-data="{ pestaña: 'conexion' }" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-800 sm:p-6">
                @if ($servicioEditandoId !== null)
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-300">Servicio #{{ $servicioEditandoId }}</p>
                    <h2 class="mt-1 text-lg font-bold">{{ $clienteIdentificacion }}</h2>
                    <p class="mt-1 text-xs text-zinc-500">Titular, tarifa y fecha de activación original se conservan.</p>
                @else
                    <h2 class="text-lg font-bold">Nueva conexión</h2>
                    <p class="mt-1 text-xs text-zinc-500">La primera tarifa es obligatoria para crear el registro; no activa automáticamente la facturación del cliente.</p>
                @endif
                <div class="mt-5 grid grid-cols-3 gap-2" role="tablist" aria-label="Datos de la conexión">
                    <button type="button" role="tab" @click="pestaña='conexion'" :aria-selected="pestaña==='conexion'" :class="pestaña==='conexion' ? 'border-blue-500 bg-blue-600 text-white' : 'border-zinc-300 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300'" class="rounded-lg border px-2 py-3 text-xs font-bold sm:text-sm">1. Conexión</button>
                    <button type="button" role="tab" @click="pestaña='red'" :aria-selected="pestaña==='red'" :class="pestaña==='red' ? 'border-blue-500 bg-blue-600 text-white' : 'border-zinc-300 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300'" class="rounded-lg border px-2 py-3 text-xs font-bold sm:text-sm">2. Equipo y red</button>
                    <button type="button" role="tab" @click="pestaña='estado'" :aria-selected="pestaña==='estado'" :class="pestaña==='estado' ? 'border-blue-500 bg-blue-600 text-white' : 'border-zinc-300 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300'" class="rounded-lg border px-2 py-3 text-xs font-bold sm:text-sm">3. Estado</button>
                </div>
            </div>

            <form wire:submit="guardar" wire:key="form-servicio-{{ $servicioEditandoId ?? 'nuevo' }}" novalidate class="p-5 sm:p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
                        <p class="font-bold">Revisa los datos antes de guardar:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div x-show="pestaña==='conexion'" role="tabpanel" class="space-y-5">
                    <div>
                        <h3 class="text-base font-bold">Datos principales</h3>
                        <p class="mt-1 text-xs text-zinc-500">Tecnología, plan contratado y velocidad asignada.</p>
                    </div>
                    @if ($servicioEditandoId === null)
                        <div>
                            <label class="mb-2 block text-sm font-semibold" for="servicio-cliente">Cliente *</label>
                            <select id="servicio-cliente" wire:model="cliente_id" required class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="">Seleccionar cliente existente...</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->codigo }} — {{ $cliente->nombre }}</option>
                                @endforeach
                            </select>
                            @error('cliente_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold" for="servicio-tipo">Tipo de conexión *</label>
                            <select id="servicio-tipo" wire:model="tipo_conexion" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="fibra">Fibra óptica</option>
                                <option value="inalambrico">Inalámbrico / Antena</option>
                            </select>
                            @error('tipo_conexion') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold" for="servicio-plan">Nombre del plan (opcional)</label>
                            <input id="servicio-plan" type="text" wire:model="nombre_plan" maxlength="100" placeholder="Ej. Plan Hogar" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            @error('nombre_plan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold" for="servicio-velocidad">Velocidad (Mbps)</label>
                            <input id="servicio-velocidad" type="number" min="1" step="1" wire:model="velocidad_mbps" placeholder="Ej. 100" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            @error('velocidad_mbps') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        @if ($servicioEditandoId === null)
                            <div>
                                <label for="servicio-tarifa" class="mb-2 block text-sm font-semibold">Tarifa inicial de este servicio (S/) *</label>
                                <input id="servicio-tarifa" type="number" step="0.01" min="0.01" required wire:model="tarifa_mensual" placeholder="Ej. 60.00" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                                <p class="mt-1 text-xs text-zinc-500">Solo para crear el servicio y su primer historial de tarifa. La facturación oficial del cliente se configura por separado.</p>
                                @error('tarifa_mensual') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="servicio-fecha" class="mb-2 block text-sm font-semibold">Fecha de activación (opcional)</label>
                                <input id="servicio-fecha" type="date" wire:model="fecha_activacion" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                                @error('fecha_activacion') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-4">
                                <p class="text-xs font-semibold text-zinc-500">Tarifa registrada — solo lectura</p>
                                <p class="mt-1 text-xl font-bold">S/ {{ number_format((float) $tarifa_mensual, 2) }}</p>
                                <p class="mt-1 text-xs text-zinc-500">No se puede cambiar desde la edición técnica. Su historial se mantiene por separado.</p>
                            </div>
                            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <p class="text-xs font-semibold text-zinc-500">Activación original — solo lectura</p>
                                <p class="mt-1 text-sm font-semibold">{{ $fecha_activacion ? \Carbon\Carbon::parse($fecha_activacion)->format('d/m/Y') : 'Sin registrar' }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="pestaña='red'" class="rounded-lg border border-blue-500/30 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-300">Siguiente: Equipo y red →</button>
                    </div>
                </div>

                <div x-show="pestaña==='red'" x-cloak role="tabpanel" class="space-y-5">
                    <div>
                        <h3 class="text-base font-bold">Equipo y red</h3>
                        <p class="mt-1 text-xs text-zinc-500">Estos campos son opcionales; su ausencia no significa que el servicio esté averiado.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold" for="servicio-direccion">Dirección de instalación</label>
                        <input id="servicio-direccion" type="text" wire:model="direccion_instalacion" placeholder="Lugar de la conexión" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                        @error('direccion_instalacion') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold" for="servicio-ip">IP asignada (opcional)</label>
                            <input id="servicio-ip" type="text" wire:model="ip_asignada" placeholder="Ej. 192.168.1.10" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            @error('ip_asignada') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold" for="servicio-mac">MAC del equipo (opcional)</label>
                            <input id="servicio-mac" type="text" wire:model="mac_equipo" maxlength="30" placeholder="AA:BB:CC:DD:EE:FF" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            @error('mac_equipo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold" for="servicio-notas">Observaciones técnicas</label>
                        <textarea id="servicio-notas" rows="3" wire:model="observaciones" placeholder="Equipos, incidencia o referencias de instalación" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700"></textarea>
                        @error('observaciones') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-wrap justify-between gap-2">
                        <button type="button" @click="pestaña='conexion'" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">← Volver</button>
                        <button type="button" @click="pestaña='estado'" class="rounded-lg border border-blue-500/30 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-300">Siguiente: Estado →</button>
                    </div>
                </div>

                <div x-show="pestaña==='estado'" x-cloak role="tabpanel" class="space-y-5">
                    <div>
                        <h3 class="text-base font-bold">Estado administrativo de la conexión</h3>
                        <p class="mt-1 text-sm text-zinc-500">Este estado describe el servicio en el sistema; no ejecuta un corte físico ni una orden MikroTik.</p>
                    </div>
                    <div class="max-w-md">
                        <label for="servicio-estado" class="mb-2 block text-sm font-semibold">Estado técnico</label>
                        <select id="servicio-estado" wire:model="estado" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="activo">Activo</option>
                            <option value="suspendido">Suspendido</option>
                        </select>
                        @error('estado') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-800 dark:text-amber-200">
                        Suspender una conexión no detiene automáticamente la facturación. Los cargos y pagos se administran desde Cobros y deudas.
                    </div>
                    <button type="button" @click="pestaña='red'" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">← Volver a Equipo y red</button>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    <button type="button" wire:click="volverListado" class="rounded-xl border border-zinc-300 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardar" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="guardar">{{ $servicioEditandoId !== null ? 'Guardar cambios técnicos' : 'Registrar servicio' }}</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </form>
        </section>

    @elseif ($modoPantalla === 'tarifa')
        <div class="rounded-2xl bg-gradient-to-r from-slate-950 to-blue-900 px-5 py-6 text-white sm:px-7">
            <button type="button" wire:click="volverListado" class="text-xs font-semibold text-blue-200 hover:text-white">← Volver a Servicios</button>
            <h1 class="mt-2 text-2xl font-bold">Cambiar tarifa del servicio</h1>
            <p class="mt-1 text-sm text-blue-100/80">Esta acción modifica el historial de tarifa técnica del servicio. No modifica cargos ya emitidos ni la facturación oficial del cliente.</p>
        </div>
    <!-- CAMBIAR TARIFA -->
    @if ($servicioTarifaId !== null)

        <section class="rounded-2xl border border-blue-500/40
                        bg-white p-6 shadow-sm
                        dark:bg-zinc-900">

            <div class="mb-6">
                <p class="text-xs font-semibold uppercase
                        tracking-widest text-blue-500">
                    Historial de tarifa del servicio
                </p>

                <h2 class="mt-2 text-2xl font-bold">
                    Cambiar tarifa
                </h2>

                <p class="mt-2 text-sm text-zinc-500">
                    Servicio seleccionado:
                    #{{ $servicioTarifaId }}
                </p>

                <p class="mt-2 text-sm text-zinc-500">
                    Registra una subida o bajada de precio.
                    La tarifa anterior se conservará
                    en el historial.
                </p>
            </div>

            <form
                wire:key="cambio-tarifa-{{ $servicioTarifaId }}"
                wire:submit="guardarCambioTarifa"
                class="space-y-5">

                <div class="grid gap-5 md:grid-cols-2">

                    <!-- NUEVA MENSUALIDAD -->
                    <div>
                        <label for="nuevoMonto"
                            class="mb-2 block text-sm font-semibold">
                            Nueva mensualidad (S/) *
                        </label>

                        <input
                            id="nuevoMonto"
                            type="number"
                            step="0.01"
                            min="0.01"
                            max="10000"
                            required
                            wire:model="nuevoMonto"
                            placeholder="50.00"
                            class="w-full rounded-xl border
                                border-zinc-300 bg-transparent
                                px-4 py-3 text-sm
                                focus:border-blue-500
                                focus:outline-none
                                dark:border-zinc-700">

                        @error('nuevoMonto')
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- FECHA -->
                    <div>
                        <label for="nuevaVigencia"
                            class="mb-2 block text-sm font-semibold">
                            Fecha de vigencia *
                        </label>

                        <input
                            id="nuevaVigencia"
                            type="date"
                            required
                            wire:model="nuevaVigencia"
                            min="{{ today()->toDateString() }}"
                            class="w-full rounded-xl border
                                border-zinc-300 bg-transparent
                                px-4 py-3 text-sm
                                focus:border-blue-500
                                focus:outline-none
                                dark:border-zinc-700">

                        @error('nuevaVigencia')
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

                <!-- MOTIVO -->
                <div>
                    <label for="motivoCambio"
                        class="mb-2 block text-sm font-semibold">
                        Motivo del cambio *
                    </label>

                    <textarea
                        id="motivoCambio"
                        wire:model="motivoCambio"
                        rows="3"
                        required
                        maxlength="255"
                        placeholder="Ej. Reducción de mensualidad acordada con el cliente"
                        class="w-full rounded-xl border
                            border-zinc-300 bg-transparent
                            px-4 py-3 text-sm
                            focus:border-blue-500
                            focus:outline-none
                            dark:border-zinc-700"></textarea>

                    @error('motivoCambio')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- AVISO -->
                <div class="rounded-xl bg-blue-50 p-4
                            text-sm text-blue-800
                            dark:bg-blue-950/40
                            dark:text-blue-200">

                    Este cambio se registrará en el
                    historial de tarifas del servicio.
                    No creará una conexión nueva.

                </div>

                <!-- BOTONES -->
                <div class="flex flex-wrap justify-end gap-3
                            border-t border-zinc-200 pt-5
                            dark:border-zinc-800">

                    <button
                        type="button"
                        wire:click="cerrarCambioTarifa"
                        class="rounded-xl border border-zinc-300
                            px-6 py-3 text-sm font-semibold
                            dark:border-zinc-700">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="guardarCambioTarifa"
                        class="rounded-xl bg-emerald-600
                            px-6 py-3 text-sm font-bold
                            text-white hover:bg-emerald-700
                            disabled:opacity-50">

                        <span wire:loading.remove
                            wire:target="guardarCambioTarifa">
                            Confirmar cambio de tarifa
                        </span>

                        <span wire:loading
                            wire:target="guardarCambioTarifa">
                            Guardando...
                        </span>

                    </button>

                </div>

            </form>

        </section>

    @endif





          
    @else
        <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-slate-950 to-blue-900 px-5 py-6 text-white sm:px-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-200">ULTRA / Clientes y red</p>
                <h1 class="mt-2 text-2xl font-bold">Servicios de Internet</h1>
                <p class="mt-1 text-sm text-blue-100/80">Centro técnico de conexiones, velocidades, instalaciones y estados.</p>
            </div>
            @if(\App\Support\IspAccess::allows(auth()->user(), 'servicios.create'))
<button type="button" wire:click="nuevo" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-500">+ Nueva conexión</button>
@endif
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">Conexiones registradas</p><p class="mt-2 text-2xl font-bold">{{ $totalServicios }}</p>
            </div>
            <div class="rounded-xl border border-emerald-600/30 bg-emerald-600/5 p-4">
                <p class="text-xs text-emerald-700 dark:text-emerald-400">Activas</p><p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $serviciosActivos }}</p>
            </div>
            <div class="rounded-xl border border-amber-600/30 bg-amber-600/5 p-4">
                <p class="text-xs text-amber-700 dark:text-amber-400">Suspendidas</p><p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $serviciosSuspendidos }}</p>
            </div>
            <div class="rounded-xl border border-blue-600/30 bg-blue-600/5 p-4">
                <p class="text-xs text-blue-700 dark:text-blue-300">Velocidad sin registrar</p><p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $serviciosPorRevisar }}</p>
            </div>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2" aria-label="Vista de conexiones">
                    <button type="button" wire:click="verConexiones" class="rounded-lg px-4 py-2 text-sm font-bold {{ !$soloPorRevisar ? 'bg-blue-600 text-white' : 'border border-zinc-300 dark:border-zinc-700' }}">Todas las conexiones</button>
                    <button type="button" wire:click="verPorRevisar" class="rounded-lg px-4 py-2 text-sm font-bold {{ $soloPorRevisar ? 'bg-blue-600 text-white' : 'border border-zinc-300 dark:border-zinc-700' }}">Por revisar ({{ $serviciosPorRevisar }})</button>
                </div>
                <button type="button" wire:click="limpiarFiltros" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">Limpiar filtros</button>
            </div>
            @if ($soloPorRevisar)
                <div class="mt-3 rounded-xl border border-blue-500/20 bg-blue-500/5 p-3 text-xs text-zinc-600 dark:text-zinc-300">Por revisar = conexión sin velocidad Mbps registrada. La IP y MAC son opcionales; esta lista no implica cortes ni deudas.</div>
            @endif
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filtro-servicio-buscar" class="mb-1 block text-xs text-zinc-500">Cliente, código, IP, MAC o plan</label>
                    <input id="filtro-servicio-buscar" type="search" wire:model.live.debounce.350ms="buscar" placeholder="Buscar conexión..." class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 text-sm dark:border-zinc-700">
                </div>
                <div>
                    <label for="filtro-servicio-localidad" class="mb-1 block text-xs text-zinc-500">Localidad</label>
                    <select id="filtro-servicio-localidad" wire:model.live="filtroLocalidad" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="todas">Todas las localidades</option>
                        @foreach ($localidades as $localidad)
                            <option value="{{ $localidad }}">{{ ucfirst($localidad) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filtro-servicio-estado" class="mb-1 block text-xs text-zinc-500">Estado técnico</label>
                    <select id="filtro-servicio-estado" wire:model.live="filtroEstado" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="todos">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="suspendido">Suspendidos</option>
                    </select>
                </div>
                <div>
                    <label for="filtro-servicio-tipo" class="mb-1 block text-xs text-zinc-500">Tecnología</label>
                    <select id="filtro-servicio-tipo" wire:model.live="filtroTipo" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="todos">Todas las tecnologías</option>
                        <option value="fibra">Fibra óptica</option>
                        <option value="inalambrico">Inalámbrico / Antena</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800 sm:p-5">
                <div><h2 class="text-base font-bold">{{ $soloPorRevisar ? 'Conexiones con datos por completar' : 'Conexiones registradas' }}</h2><p class="mt-1 text-xs text-zinc-500">{{ $servicios->total() }} resultados · la tarifa se muestra solo como referencia.</p></div>
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">15 por página</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-zinc-100 text-xs dark:bg-zinc-800">
                        <tr><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Localidad</th><th class="px-4 py-3">Conexión</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Equipo / tarifa</th><th class="px-4 py-3 text-right">Acciones</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($servicios as $servicio)
                            <tr wire:key="servicio-{{ $servicio->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $servicio->cliente?->nombre ?? 'Cliente no disponible' }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $servicio->cliente?->codigo ?? '-' }} · {{ $servicio->cliente?->telefono ?? 'Sin celular' }}</p>
                                </td>
                                <td class="px-4 py-3">{{ ucfirst($servicio->cliente?->sector ?: 'Sin registrar') }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $servicio->tipo_conexion === 'fibra' ? 'Fibra óptica' : 'Inalámbrico' }}</p>
                                    @if ($servicio->velocidad_mbps)
                                        <p class="mt-1 text-xs text-zinc-500">{{ $servicio->velocidad_mbps }} Mbps · {{ $servicio->nombre_plan ?: 'Sin nombre de plan' }}</p>
                                    @else
                                        <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-300">Velocidad sin registrar</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $servicio->estado === 'activo' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">{{ $servicio->estado === 'activo' ? 'Activo' : 'Suspendido' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs">{{ $servicio->ip_asignada ?: 'IP sin registrar' }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">Referencia: S/ {{ number_format((float) $servicio->tarifa_mensual, 2) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if(\App\Support\IspAccess::allows(auth()->user(), 'servicios.edit'))
<button type="button" wire:click="editar({{ $servicio->id }})" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700">Configurar</button>
@endif
                                        @if ($servicio->cliente)
                                            @if(\App\Support\IspAccess::allows(auth()->user(), 'clientes.view'))
<a href="{{ route('clientes.show', $servicio->cliente_id) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">Ficha</a>
@endif
                                        @endif
                                        @if(\App\Support\IspAccess::allows(auth()->user(), 'tarifas.manage'))
<button type="button" wire:click="abrirCambioTarifa({{ $servicio->id }})" title="Historial tarifario (acción separada de la configuración técnica)" class="rounded-lg px-2 py-2 text-xs font-semibold text-zinc-500 underline decoration-dotted hover:text-blue-500">Tarifa</button>
@endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-8 text-center text-sm text-zinc-500">No encontramos conexiones con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $servicios->links() }}</div>
        </section>
        <p class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-3 text-xs text-zinc-600 dark:text-zinc-300">Esta pantalla administra conexiones técnicas. Cambiar el estado aquí no corta Internet en MikroTik ni cambia automáticamente la facturación. Los pagos se gestionan en Cobros y deudas.</p>
    @endif
</div>
