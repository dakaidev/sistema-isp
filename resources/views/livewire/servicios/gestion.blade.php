<div class="mx-auto max-w-7xl space-y-6 pb-10">

    <!-- ENCABEZADO -->
    <div class="rounded-3xl bg-gradient-to-r
                from-slate-900 to-blue-900 p-7 text-white">

        <p class="text-xs font-bold uppercase
                  tracking-widest text-blue-200">
            Sistema ISP / Operaciones
        </p>

        <h1 class="mt-3 text-3xl font-bold">
            Servicios de Internet
        </h1>

        <p class="mt-2 text-sm text-blue-100">
            Administra conexiones de fibra óptica
            e internet inalámbrico.
        </p>
    </div>

    @if (session()->has('mensaje'))
        <div class="rounded-xl bg-green-100 p-4
                    font-medium text-green-800">
            {{ session('mensaje') }}
        </div>
    @endif

    <!-- ESTADÍSTICAS -->
    <div class="grid gap-4 sm:grid-cols-2">

        <div class="rounded-2xl border border-zinc-200
                    bg-white p-6 shadow-sm
                    dark:border-zinc-800 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500">
                Total de servicios
            </p>

            <h2 class="mt-2 text-3xl font-bold">
                {{ $totalServicios }}
            </h2>
        </div>

        <div class="rounded-2xl border border-zinc-200
                    bg-white p-6 shadow-sm
                    dark:border-zinc-800 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500">
                Servicios activos
            </p>

            <h2 class="mt-2 text-3xl font-bold
                       text-green-600">
                {{ $serviciosActivos }}
            </h2>
        </div>

    </div>

    @if ($servicioTarifaId === null)

    <!-- FORMULARIO -->
    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="border-b border-zinc-200 p-6
                    dark:border-zinc-800">


            <h2 class="text-xl font-bold">

                {{ $servicioEditandoId !== null
                    ? 'Editar servicio'
                    : 'Registrar nuevo servicio' }}

            </h2>

            @if ($servicioEditandoId !== null)

                <p class="mt-2 text-sm text-amber-600">
                    Estás modificando una conexión existente.
                    La tarifa mensual y la fecha original
                    de activación se conservarán.
                </p>

            @endif
                

            <p class="mt-1 text-sm text-zinc-500">
                Selecciona al cliente y configura
                su conexión y mensualidad.
            </p>
        </div>

        <form
            wire:submit="guardar"
            wire:key="form-servicio-{{ $servicioEditandoId ?? 'nuevo' }}"
            class="space-y-6 p-6">


            <div class="grid gap-5 md:grid-cols-2">

                <!-- CLIENTE -->
                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium">
                        Cliente *
                    </label>

                    <select wire:model="cliente_id"
                            @disabled($servicioEditandoId !== null)
                            required
                            class="w-full rounded-xl border
                                   border-zinc-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-zinc-700
                                   dark:bg-zinc-900">

                        <option value="">
                            Seleccionar cliente...
                        </option>

                        @foreach ($clientes as $cliente)

                            <option value="{{ $cliente->id }}">
                                {{ $cliente->codigo }}
                                — {{ $cliente->nombre }}
                            </option>

                        @endforeach

                    </select>

                    @error('cliente_id')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- TECNOLOGÍA -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Tipo de conexión *
                    </label>

                    <select wire:model="tipo_conexion"
                            class="w-full rounded-xl border
                                   border-zinc-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-zinc-700
                                   dark:bg-zinc-900">

                        <option value="fibra">
                            Fibra óptica
                        </option>

                        <option value="inalambrico">
                            Inalámbrico / Antena
                        </option>

                    </select>

                    @error('tipo_conexion')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- PLAN -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Nombre del plan
                    </label>

                    <input type="text"
                           wire:model="nombre_plan"
                           placeholder="Ej. Plan Hogar 100 Mbps"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">

                    @error('nombre_plan')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- VELOCIDAD -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Velocidad (Mbps)
                    </label>

                    <input type="number"
                           min="1"
                           wire:model="velocidad_mbps"
                           placeholder="100"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">

                    @error('velocidad_mbps')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- TARIFA -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Mensualidad (S/) *
                    </label>

                    <input type="number"
                           step="0.01"
                           min="0.01"
                           required
                           wire:model="tarifa_mensual"
                           @disabled($servicioEditandoId !== null)
                           placeholder="60.00"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">

                    @error('tarifa_mensual')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- ACTIVACIÓN -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Fecha de activación (opcional)
                    </label>

                    <input type="date"
                           wire:model="fecha_activacion"
                           @disabled($servicioEditandoId !== null)
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">

                    @error('fecha_activacion')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- ESTADO -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Estado del servicio
                    </label>

                    <select wire:model="estado"
                            class="w-full rounded-xl border
                                   border-zinc-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-zinc-700
                                   dark:bg-zinc-900">

                        <option value="activo">Activo</option>
                        <option value="suspendido">Suspendido</option>

                    </select>

                    @error('estado')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- DIRECCIÓN DE INSTALACIÓN -->
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium">
                        Dirección de instalación
                    </label>

                    <input type="text"
                           wire:model="direccion_instalacion"
                           placeholder="Ubicación de la conexión"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">
                </div>

                <!-- IP -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        IP asignada (opcional)
                    </label>

                    <input type="text"
                           wire:model="ip_asignada"
                           placeholder="192.168.1.10"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">

                    @error('ip_asignada')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- MAC -->
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        MAC del equipo (opcional)
                    </label>

                    <input type="text"
                           wire:model="mac_equipo"
                           placeholder="AA:BB:CC:DD:EE:FF"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  dark:border-zinc-700">

                    @error('mac_equipo')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- OBSERVACIONES -->
                <div class="md:col-span-2">

                    <label class="mb-2 block text-sm font-medium">
                        Observaciones
                    </label>

                    <textarea wire:model="observaciones"
                              rows="3"
                              placeholder="Notas de instalación..."
                              class="w-full rounded-xl border
                                     border-zinc-300 bg-transparent
                                     px-4 py-3 text-sm
                                     dark:border-zinc-700"></textarea>
                </div>

            </div>


            <!-- BOTONES DEL FORMULARIO -->
            <div class="flex flex-wrap justify-end gap-3
                        border-t border-zinc-200 pt-5
                        dark:border-zinc-800">

                @if ($servicioEditandoId !== null)

                    <button
                        type="button"
                        wire:click="cancelarEdicion"
                        class="rounded-xl border border-zinc-300
                            px-6 py-3 text-sm font-semibold
                            hover:bg-zinc-100
                            dark:border-zinc-700
                            dark:hover:bg-zinc-800">

                        Cancelar edición

                    </button>

                @endif

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="guardar"
                    class="rounded-xl bg-blue-600
                        px-7 py-3 text-sm font-bold
                        text-white transition
                        hover:bg-blue-700
                        disabled:opacity-50">

                    <span wire:loading.remove wire:target="guardar">
                        {{ $servicioEditandoId !== null
                            ? 'Guardar cambios'
                            : 'Guardar servicio' }}
                    </span>

                    <span wire:loading wire:target="guardar">
                        Guardando...
                    </span>

                </button>

            </div>
        
        </form>

    </div>  
    
    @endif


    
    <!-- SECCIÓN DE CAMBIO DE TARIFA -->
    
    <!-- CAMBIAR TARIFA -->
    @if ($servicioTarifaId !== null)

        <section class="rounded-2xl border border-blue-500/40
                        bg-white p-6 shadow-sm
                        dark:bg-zinc-900">

            <div class="mb-6">
                <p class="text-xs font-semibold uppercase
                        tracking-widest text-blue-500">
                    Gestión de mensualidades
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





          
    <!-- TABLA DE SERVICIOS -->
    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="p-6">
            <h2 class="text-xl font-bold">
                Servicios registrados
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Conexiones asociadas a nuestros clientes.
            </p>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full min-w-[800px] text-left text-sm">

                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-4">Cliente</th>
                        <th class="p-4">Conexión</th>
                        <th class="p-4">Plan</th>
                        <th class="p-4">Mensualidad</th>
                        <th class="p-4">Activación</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200
                              dark:divide-zinc-800">

                    @forelse ($servicios as $servicio)

                        <tr wire:key="servicio-{{ $servicio->id }}"
                            class="hover:bg-zinc-50
                                   dark:hover:bg-zinc-800/50">

                            <td class="p-4">
                                <p class="font-semibold">
                                    {{ $servicio->cliente->nombre }}
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $servicio->cliente->codigo }}
                                </p>
                            </td>

                            <td class="p-4">
                                {{ $servicio->tipo_conexion === 'fibra'
                                    ? 'Fibra óptica'
                                    : 'Inalámbrico' }}
                            </td>

                            <td class="p-4">
                                {{ $servicio->nombre_plan ?: '-' }}
                            </td>

                            <td class="p-4 font-bold">
                                S/ {{ number_format(
                                    (float) $servicio->tarifa_mensual,
                                    2
                                ) }}
                            </td>

                            <td class="p-4">
                                {{ $servicio->fecha_activacion?->format('d/m/Y') ?? 'Sin fecha' }}
                            </td>

                            <td class="p-4">
                                @if ($servicio->estado === 'activo')
                                    <span class="rounded-full
                                                 bg-green-100
                                                 px-3 py-1 text-xs
                                                 font-bold text-green-700">
                                        Activo
                                    </span>
                                @else
                                    <span class="rounded-full
                                                 bg-amber-100
                                                 px-3 py-1 text-xs
                                                 font-bold text-amber-700">
                                        Suspendido
                                    </span>
                                @endif
                            </td>

                            
                            <td class="p-4">

                                <button
                                    type="button"
                                    wire:click="editar({{ $servicio->id }})"
                                    class="inline-flex items-center gap-2
                                        rounded-xl bg-blue-100
                                        px-4 py-2 text-xs font-bold
                                        text-blue-700 transition
                                        hover:bg-blue-200
                                        dark:bg-blue-950
                                        dark:text-blue-300
                                        dark:hover:bg-blue-900">

                                    <flux:icon.pencil-square class="size-4" />

                                    Editar

                                </button>
                                
                                <button
                                    type="button"
                                    wire:click="abrirCambioTarifa({{ $servicio->id }})"
                                    class="ml-2 inline-flex items-center
                                        rounded-xl bg-emerald-100
                                        px-4 py-2 text-xs font-bold
                                        text-emerald-700 transition
                                        hover:bg-emerald-200
                                        dark:bg-emerald-950
                                        dark:text-emerald-300">

                                    Cambiar tarifa

                                </button>

                            </td>


                        </tr>

                    @empty

                        <tr>
                            <td colspan="7"
                                class="p-10 text-center text-zinc-500">
                                Todavía no hay servicios registrados.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <div class="border-t border-zinc-200 p-5
                    dark:border-zinc-800">
            {{ $servicios->links() }}
        </div>

    </div>

</div>
