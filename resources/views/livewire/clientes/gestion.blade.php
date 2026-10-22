<div class="mx-auto w-full max-w-7xl space-y-6 pb-10">

    <!-- ENCABEZADO -->
    <div class="rounded-3xl bg-gradient-to-r
                from-slate-900 to-blue-900 p-7 text-white">

        <p class="text-xs font-bold uppercase tracking-widest
                  text-blue-200">
            Sistema ISP / Administración
        </p>

        <h1 class="mt-3 text-3xl font-bold">
            Gestión de Clientes
        </h1>

        <p class="mt-2 text-sm text-blue-100">
            Un solo lugar para consultar clientes, su tarifa de facturación y los datos de la conexión.
            Cobranza y Mensualidades siguen utilizando los mismos registros de MySQL.
        </p>
    </div>

    <!-- MENSAJE DE ÉXITO -->
    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-green-300
                    bg-green-50 p-4 text-sm font-medium
                    text-green-800 dark:border-green-800
                    dark:bg-green-950 dark:text-green-200">
            {{ session('mensaje') }}
        </div>
    @endif

    <!-- ESTADÍSTICA -->
    <div class="rounded-2xl border border-zinc-200
                bg-white p-6 shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <p class="text-sm font-medium text-zinc-500">
            Total de clientes registrados
        </p>

        <h2 class="mt-2 text-4xl font-bold
                   text-blue-600 dark:text-blue-400">
            {{ $totalClientes }}
        </h2>

        <p class="mt-2 text-xs text-zinc-500">
            Registros almacenados en nuestra base de datos.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-emerald-600/30 bg-emerald-500/5 p-5 dark:bg-emerald-950/20">
            <p class="text-sm text-zinc-500">Clientes con facturación habilitada</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $totalFacturacion }}</p>
            <p class="mt-1 text-xs text-zinc-500">Son los que pueden recibir mensualidades automáticas; no equivale a conexiones técnicas.</p>
        </div>
        <div class="rounded-2xl border border-blue-600/30 bg-blue-500/5 p-5 dark:bg-blue-950/20">
            <p class="text-sm text-zinc-500">Clientes con conexión técnica registrada</p>
            <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $totalConexion }}</p>
            <p class="mt-1 text-xs text-zinc-500">Un registro de conexión es opcional y no crea deudas por sí mismo.</p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-blue-600/30 bg-blue-500/5 p-4">
            <p class="text-sm text-zinc-500">Sede Upina</p>
            <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $totalUpina }}</p>
        </div>
        <div class="rounded-2xl border border-indigo-600/30 bg-indigo-500/5 p-4">
            <p class="text-sm text-zinc-500">Sede Calasuca</p>
            <p class="mt-1 text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ $totalCalasuca }}</p>
        </div>
        <div class="rounded-2xl border border-amber-600/30 bg-amber-500/5 p-4">
            <p class="text-sm text-zinc-500">Sin sede revisada</p>
            <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $totalSinSede }}</p>
            <p class="mt-1 text-xs text-zinc-500">Clientes existentes: asignar después de confirmar su ubicación.</p>
        </div>
    </div>

    <!-- ESTADOS ADMINISTRATIVOS DE INTERNET -->
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-emerald-600/30 p-4 dark:bg-emerald-950/10">
            <p class="text-sm text-zinc-500">Internet activo (registrado)</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $totalActivos }}</p>
        </div>
        <div class="rounded-2xl border border-amber-600/30 p-4 dark:bg-amber-950/10">
            <p class="text-sm text-zinc-500">Internet suspendido (registrado)</p>
            <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $totalSuspendidos }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-500/30 p-4">
            <p class="text-sm text-zinc-500">Baja definitiva</p>
            <p class="mt-1 text-2xl font-bold">{{ $totalBajas }}</p>
        </div>
        <div class="rounded-2xl border border-blue-500/30 p-4">
            <p class="text-sm text-zinc-500">Sin ficha tecnica</p>
            <p class="mt-1 text-2xl font-bold">{{ $totalSinRegistro }}</p>
        </div>
    </div>
    <p class="text-xs text-zinc-500">Son estados administrativos registrados en MySQL; no verifican si el servidor cortó la conexión. Facturación y estado de internet son independientes.</p>

    <!-- FORMULARIO -->
    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="border-b border-zinc-200 p-6
                    dark:border-zinc-800">

            <h2 class="text-xl font-bold">
                {{ $clienteEditandoId !== null 
                    ? 'Editar cliente'
                    : 'Nuevo cliente'}}
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Completa los datos del abonado.
                Los campos con * son obligatorios.
            </p>
        </div>

        <form 
            id = "formulario-cliente"
            wire:key="form-cliente-{{ $clienteEditandoId ?? 'nuevo' }}"
            wire:submit="guardar"
            class="space-y-6 p-6">

            <div class="grid gap-5 md:grid-cols-2
                        xl:grid-cols-3">

                <!-- CÓDIGO -->
                <div>
                    <label for="codigo"
                           class="mb-2 block text-sm font-medium">
                        Código del cliente *
                    </label>

                    <input id="codigo"
                           type="text"
                           wire:model="codigo"
                           placeholder="CLI001"
                           required
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('codigo')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- NOMBRE -->
                <div>
                    <label for="nombre"
                           class="mb-2 block text-sm font-medium">
                        Nombre completo *
                    </label>

                    <input id="nombre"
                           type="text"
                           wire:model="nombre"
                           placeholder="Nombre y apellidos"
                           required
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('nombre')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- DOCUMENTO -->
                <div>
                    <label for="documento"
                           class="mb-2 block text-sm font-medium">
                        DNI / Documento
                    </label>

                    <input id="documento"
                           type="text"
                           wire:model="documento"
                           placeholder="Documento de identidad"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('documento')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- TELÉFONO -->
                <div>
                    <label for="telefono"
                           class="mb-2 block text-sm font-medium">
                        Celular
                    </label>

                    <input id="telefono"
                           type="tel"
                           wire:model="telefono"
                           placeholder="Número de contacto"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('telefono')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- SEDE: en alta y edición. Los clientes antiguos pueden seguir sin asignar. -->
                <div>
                    <label for="sede-cliente" class="mb-2 block text-sm font-medium">Sede / servidor {{ $clienteEditandoId === null ? '*' : '' }}</label>
                    <select id="sede-cliente" wire:model="sede" @required($clienteEditandoId === null)
                        class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                        <option value="">{{ $clienteEditandoId !== null ? 'Sin asignar / pendiente de verificar' : 'Seleccionar sede...' }}</option>
                        <option value="upina">Upina — servidor de Upina</option>
                        <option value="calasuca">Calasuca — servidor de Calasuca</option>
                    </select>
                    @error('sede') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- GPS: UNA casilla visible; latitud y longitud separadas en la base de datos. -->
                <div class="xl:col-span-2">
                    <label for="ubicacion-gps" class="mb-2 block text-sm font-medium">Ubicación GPS (opcional)</label>
                    <input id="ubicacion-gps" type="text" wire:model="ubicacionGps"
                        placeholder="Ej. -13.8824429, -70.0870094"
                        autocomplete="off"
                        class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                    <p class="mt-1 text-xs text-zinc-500">Pega latitud y longitud juntas, separadas por una coma, como en Google Maps. También puedes dejarlo vacío.</p>
                    @error('ubicacionGps') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- DIRECCIÓN -->
                <div>
                    <label for="direccion"
                           class="mb-2 block text-sm font-medium">
                        Dirección
                    </label>

                    <input id="direccion"
                           type="text"
                           wire:model="direccion"
                           placeholder="Dirección del cliente"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('direccion')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- FECHAS: HISTORICO DEL CLIENTE. Independientes del inicio de cobro. -->
                <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <h3 class="mb-2 font-semibold">Fechas del servicio / cliente</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="fecha-instalacion" class="mb-2 block text-sm font-medium">Fecha de instalación (opcional)</label>
                            <input id="fecha-instalacion" type="date" wire:model="fechaInstalacion"
                                class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                            @error('fechaInstalacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="fecha-baja" class="mb-2 block text-sm font-medium">Fecha de baja definitiva (opcional)</label>
                            <input id="fecha-baja" type="date" wire:model="fechaBaja"
                                class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                            @error('fechaBaja') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">Se conservan como datos históricos. Registrar una baja aquí no cancela automáticamente el cobro, ni borra pagos, deudas o servicios. Las suspensiones temporales se administran por separado.</p>
                </div>

                <!-- OBSERVACIONES -->
                <div class="md:col-span-2 xl:col-span-3">

                    <label for="observaciones"
                           class="mb-2 block text-sm font-medium">
                        Observaciones
                    </label>

                    <textarea id="observaciones"
                              rows="3"
                              wire:model="observaciones"
                              placeholder="Notas adicionales..."
                              class="w-full rounded-xl border
                                     border-zinc-300 bg-transparent
                                     px-4 py-3 text-sm
                                     focus:border-blue-500
                                     focus:outline-none
                                     dark:border-zinc-700"></textarea>

                    @error('observaciones')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <!-- BOTÓN GUARDAR -->

            <!-- BOTONES DEL FORMULARIO -->
            <div class="flex flex-wrap justify-end gap-3
                        border-t border-zinc-200 pt-5
                        dark:border-zinc-800">

                @if ($clienteEditandoId !== null)

                    <button
                        type="button"
                        wire:click="cancelarEdicion"
                        class="rounded-xl border border-zinc-300
                            px-6 py-3 text-sm font-semibold
                            text-zinc-700 transition
                            hover:bg-zinc-100
                            dark:border-zinc-700
                            dark:text-zinc-200
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
                        text-white shadow-lg
                        shadow-blue-600/20
                        transition hover:bg-blue-700
                        disabled:opacity-50">

                    <span wire:loading.remove wire:target="guardar">
                        {{ $clienteEditandoId !== null
                            ? 'Guardar cambios'
                            : 'Guardar cliente' }}
                    </span>

                    <span wire:loading wire:target="guardar">
                        Guardando...
                    </span>

                </button>

            </div>

        </form>
    </div>

    <!-- LISTADO -->
    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="flex flex-col gap-4 p-6
                    sm:flex-row sm:items-center
                    sm:justify-between">

            <div>
                <h2 class="text-xl font-bold">
                    Clientes registrados
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Consulta tarifa, estado de cobro y tipo de conexión. Pulsa «Configurar» para editar a ese cliente.
                </p>
            </div>

            <div class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs text-zinc-500" for="filtro-sede">Sede</label>
                    <select id="filtro-sede" wire:model.live="filtroSede" class="w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-3 text-sm dark:border-zinc-700">
                        <option value="todos">Todas</option><option value="upina">Upina</option><option value="calasuca">Calasuca</option><option value="sin_asignar">Sin asignar</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-500" for="filtro-estado">Internet</label>
                    <select id="filtro-estado" wire:model.live="filtroEstadoConexion" class="w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-3 text-sm dark:border-zinc-700">
                        <option value="todos">Todos</option><option value="activo">Activo</option><option value="suspendido">Suspendido</option><option value="baja">Baja definitiva</option><option value="sin_registro">Sin ficha técnica</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-500" for="filtro-facturacion">Facturación</label>
                    <select id="filtro-facturacion" wire:model.live="filtroFacturacion" class="w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-3 text-sm dark:border-zinc-700">
                        <option value="todos">Todas</option><option value="habilitada">Habilitada</option><option value="deshabilitada">Deshabilitada</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-500" for="filtro-plan">Plan técnico</label>
                    <select id="filtro-plan" wire:model.live="filtroPlan" class="w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-3 text-sm dark:border-zinc-700">
                        <option value="todos">Todos</option><option value="inicio">Inicio · 5 Mbps</option><option value="conecta">Conecta · 8 Mbps</option><option value="avanza">Avanza · 10 Mbps</option><option value="turbo">Turbo · 20 Mbps</option><option value="sin_plan">Sin plan definido</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-zinc-500" for="buscar-cliente">Buscar</label>
                    <input id="buscar-cliente" type="search" wire:model.live.debounce.350ms="buscar" placeholder="Código, nombre o celular" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm focus:border-blue-500 focus:outline-none dark:border-zinc-700">
                </div>
            </div>
            <button type="button" wire:click="limpiarFiltros" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Limpiar filtros</button>

        </div>

        <p class="px-6 pb-3 text-xs text-zinc-500">Los filtros se combinan. «Facturación habilitada» no significa que el cliente esté al día; revisa deudas y pagos en Cobranza.</p>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1320px] text-left text-sm">

                <thead class="bg-zinc-100 text-zinc-600
                              dark:bg-zinc-800
                              dark:text-zinc-300">

                    <tr>
                        <th class="px-5 py-4">Código</th>
                        <th class="px-5 py-4">Cliente</th>
                        <th class="px-5 py-4">Sede</th>
                        <th class="px-5 py-4">Celular / Ubicación</th>
                        <th class="px-5 py-4">Mensualidad</th>
                        <th class="px-5 py-4">Facturación</th>
                        <th class="px-5 py-4">Conexión / plan</th>
                        <th class="px-5 py-4">Acciones</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-zinc-200
                              dark:divide-zinc-800">

                    @forelse ($clientes as $cliente)

                        <tr wire:key="cliente-{{ $cliente->id }}"
                            class="transition
                                   hover:bg-zinc-50
                                   dark:hover:bg-zinc-800/60">

                            <td class="px-5 py-4">
                                <span class="rounded-lg
                                             bg-blue-100 px-3 py-1
                                             font-bold text-blue-700
                                             dark:bg-blue-950
                                             dark:text-blue-300">
                                    {{ $cliente->codigo }}
                                </span>
                            </td>

                            <td class="px-5 py-4 font-semibold">
                                {{ $cliente->nombre }}
                            </td>
                            <td class="px-5 py-4">
                                @if ($cliente->sede === 'upina')
                                    <span class="rounded-lg bg-blue-500/10 px-2 py-1 text-xs font-bold text-blue-700 dark:text-blue-300">Upina</span>
                                @elseif ($cliente->sede === 'calasuca')
                                    <span class="rounded-lg bg-indigo-500/10 px-2 py-1 text-xs font-bold text-indigo-700 dark:text-indigo-300">Calasuca</span>
                                @else
                                    <span class="text-xs font-medium text-amber-700 dark:text-amber-300">Sin asignar</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div>{{ $cliente->telefono ?: 'Sin teléfono' }}</div>
                                @if($cliente->direccion)
                                    <div class="max-w-[190px] truncate text-xs text-zinc-500" title="{{ $cliente->direccion }}">{{ $cliente->direccion }}</div>
                                @endif
                                @if($cliente->latitud !== null && $cliente->longitud !== null)
                                    <a href="{{ 'https://www.google.com/maps?q='.rawurlencode((string) $cliente->latitud.','.(string) $cliente->longitud) }}"
                                        target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-xs font-semibold text-blue-700 underline dark:text-blue-300">Ver GPS en Maps</a>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-bold">
                                {{ $cliente->tarifa_cobro !== null ? 'S/ '.number_format((float) $cliente->tarifa_cobro, 2) : 'Sin tarifa' }}
                            </td>
                            <td class="px-5 py-4">
                                @if ($cliente->cobro_habilitado)
                                    <span class="rounded-lg bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Habilitada</span>
                                @else
                                    <span class="rounded-lg bg-zinc-100 px-2 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">No habilitada</span>
                                @endif
                                <div class="mt-2 text-xs text-zinc-500">{{ $cliente->fecha_cobro ? \Carbon\Carbon::parse($cliente->fecha_cobro)->format('d/m/Y') : 'Sin inicio' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if ($cliente->fecha_baja)
                                    <span class="rounded-lg bg-zinc-700 px-2 py-1 text-xs font-semibold text-white">Baja definitiva</span>
                                @elseif ($cliente->servicio_tecnico_id && $cliente->estado_tecnico === 'suspendido')
                                    <span class="rounded-lg bg-amber-500/10 px-2 py-1 text-xs font-bold text-amber-700 dark:text-amber-300">Suspendido</span>
                                @elseif ($cliente->servicio_tecnico_id && $cliente->estado_tecnico === 'activo')
                                    <span class="rounded-lg bg-emerald-500/10 px-2 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">Activo</span>
                                @else
                                    <span class="text-xs text-zinc-500">Sin ficha técnica</span>
                                @endif
                                @if ($cliente->servicio_tecnico_id)
                                    <div class="mt-2 text-xs text-zinc-500">{{ $cliente->tipo_tecnico === 'fibra' ? 'Fibra' : 'Inalámbrico' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $cliente->plan_tecnico ?: 'Plan sin definir' }}@if($cliente->velocidad_tecnica) · {{ $cliente->velocidad_tecnica }} Mbps @endif</div>
                                @endif
                            </td>

                            <td class="px-5 py-4">

                                <button
                                    type="button"
                                    wire:click="editar({{ $cliente->id }})"
                                    x-on:click="document.getElementById('formulario-cliente').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                    class="inline-flex items-center gap-2
                                        rounded-lg bg-blue-100
                                        px-4 py-2 text-xs font-bold
                                        text-blue-700 transition
                                        hover:bg-blue-200
                                        dark:bg-blue-950
                                        dark:text-blue-300
                                        dark:hover:bg-blue-900">

                                    Editar

                                </button>
                                
                                
                                @if ($perfilClienteId === (int) $cliente->id)
                                    <button type="button" wire:click="cerrarPerfil" class="ml-2 inline-flex items-center rounded-lg bg-zinc-600 px-4 py-2 text-xs font-bold text-white hover:bg-zinc-700">Cerrar</button>
                                @else
                                    <button type="button" wire:click="abrirPerfil({{ $cliente->id }})" class="ml-2 inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Configurar</button>
                                @endif

                                <a href="{{ route('clientes.show', $cliente->id) }}"
                                wire:navigate
                                class="ml-2 inline-flex items-center
                                        rounded-lg bg-emerald-100
                                        px-4 py-2 text-xs font-bold
                                        text-emerald-700
                                        transition hover:bg-emerald-200
                                        dark:bg-emerald-950
                                        dark:text-emerald-300">

                                    Ver ficha

                                </a>


                            </td>


                        </tr>
                        @if ($perfilClienteId === (int) $cliente->id)
                            <tr wire:key="perfil-unificado-{{ $cliente->id }}">
                                <td colspan="8" class="bg-zinc-50 p-4 dark:bg-zinc-950/40 sm:p-6">
                                    @include('livewire.clientes.perfil-unificado')
                                </td>
                            </tr>
                        @endif

                    @empty

                        <tr>
                            <td colspan="8"
                                class="px-5 py-12 text-center
                                       text-zinc-500">

                                Todavía no hay clientes
                                para mostrar.

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <div class="border-t border-zinc-200 p-5
                    dark:border-zinc-800">

            {{ $clientes->links() }}

        </div>

    </div>

</div>
