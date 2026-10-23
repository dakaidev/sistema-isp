<div class="mx-auto w-full max-w-7xl space-y-5 pb-12">
    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ session('mensaje') }}</div>
    @endif

    @if ($modoPantalla === 'formulario')
        <div class="rounded-2xl bg-gradient-to-r from-slate-950 to-blue-900 px-5 py-6 text-white sm:px-7">
            <a href="{{ route('clientes.index') }}" wire:navigate class="text-xs font-semibold text-blue-200 hover:text-white">← Volver al listado</a>
            <h1 class="mt-3 text-2xl font-bold">{{ $clienteEditandoId !== null ? 'Editar cliente' : 'Nuevo cliente' }}</h1>
            <p class="mt-1 text-sm text-blue-100/80">Registra o actualiza sus datos. La conexión y la facturación se configuran por separado.</p>
        </div>
    <!-- Formulario original, conservado sin cambios en validaciones. -->

    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="border-b border-zinc-200 p-6
                    dark:border-zinc-800">

            <h2 class="text-lg font-bold">Datos del abonado</h2>

            <p class="mt-1 text-sm text-zinc-500">
                Completa los datos del abonado.
                Los campos con * son obligatorios.
            </p>
        </div>

        <form
            id="formulario-cliente"
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

                <a href="{{ route('clientes.index') }}" wire:navigate
                    class="rounded-xl border border-zinc-300 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Cancelar y volver
                </a>

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


    @else
        <!-- Vista principal: lista y filtros primero. -->
        <div class="flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-blue-900 px-5 py-6 text-white sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-blue-200">Sistema ISP · Administración</p>
                <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Gestión de Clientes</h1>
                <p class="mt-1 text-sm text-blue-100/80">Busca, filtra y administra tus abonados desde un solo lugar.</p>
            </div>
            <a href="{{ route('clientes.nuevo') }}" wire:navigate class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/30 transition hover:bg-blue-500">
                <span class="text-lg leading-none">+</span> Nuevo cliente
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium text-zinc-500">Total clientes</p>
                <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $totalClientes }}</p>
                <p class="mt-1 text-[11px] text-zinc-500">Registrados en MySQL</p>
            </div>
            <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/5 p-4">
                <p class="text-xs font-medium text-zinc-500">Internet activo</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $totalActivos }}</p>
                <p class="mt-1 text-[11px] text-zinc-500">Estado técnico registrado</p>
            </div>
            <div class="rounded-2xl border border-amber-500/25 bg-amber-500/5 p-4">
                <p class="text-xs font-medium text-zinc-500">Suspendidos</p>
                <p class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $totalSuspendidos }}</p>
                <p class="mt-1 text-[11px] text-zinc-500">Sin ejecutar cortes</p>
            </div>
            <div class="rounded-2xl border border-sky-500/25 bg-sky-500/5 p-4">
                <p class="text-xs font-medium text-zinc-500">Facturación habilitada</p>
                <p class="mt-2 text-2xl font-bold text-sky-700 dark:text-sky-300">{{ $totalFacturacion }}</p>
                <p class="mt-1 text-[11px] text-zinc-500">Independiente del estado de Internet</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="space-y-4 border-b border-zinc-200 p-4 dark:border-zinc-800 sm:p-5">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div class="md:col-span-2 xl:col-span-1">
                        <label for="buscar-cliente" class="mb-1.5 block text-xs font-semibold text-zinc-500">Buscar cliente</label>
                        <input id="buscar-cliente" type="search" wire:model.live.debounce.400ms="buscar" placeholder="Código, nombre, celular, DNI o dirección..."
                            class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none dark:border-zinc-700" />
                    </div>
                    <div>
                        <label for="filtro-sede" class="mb-1.5 block text-xs font-semibold text-zinc-500">Localidad</label>
                        <select id="filtro-sede" wire:model.live="filtroSede" class="w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-700">
                            <option value="todos">Todas</option><option value="upina">Upina</option><option value="calasuca">Calasuca</option><option value="sin_asignar">Sin asignar</option>
                        </select>
                    </div>
                    <div>
                        <label for="filtro-estado" class="mb-1.5 block text-xs font-semibold text-zinc-500">Estado de conexión</label>
                        <select id="filtro-estado" wire:model.live="filtroEstadoConexion" class="w-full rounded-xl border border-zinc-300 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-700">
                            <option value="todos">Todos</option><option value="activo">Activo</option><option value="suspendido">Suspendido</option><option value="baja">Baja definitiva</option><option value="sin_registro">Sin ficha técnica</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button type="button" wire:click="alternarFiltrosAvanzados"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-blue-300 dark:hover:bg-zinc-800">
                        {{ $mostrarFiltrosAvanzados ? '− Ocultar filtros avanzados' : '+ Filtros avanzados' }}
                        @if ($filtroFacturacion !== 'todos' || $filtroPlan !== 'todos' || $filtroDeuda !== 'todos' || $filtroRevision !== 'todos')
                            <span class="rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] text-white">Activos</span>
                        @endif
                    </button>
                    <button type="button" wire:click="limpiarFiltros" class="rounded-lg px-3 py-2 text-xs font-semibold text-zinc-500 transition hover:text-blue-600 dark:hover:text-blue-300">Limpiar filtros</button>
                </div>

                @if ($mostrarFiltrosAvanzados)
                    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-950/40 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label for="filtro-facturacion" class="mb-1.5 block text-xs font-semibold text-zinc-500">Facturación</label>
                            <select id="filtro-facturacion" wire:model.live="filtroFacturacion" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-700">
                                <option value="todos">Todas</option><option value="habilitada">Habilitada</option><option value="deshabilitada">Deshabilitada</option>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-plan" class="mb-1.5 block text-xs font-semibold text-zinc-500">Plan técnico</label>
                            <select id="filtro-plan" wire:model.live="filtroPlan" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-700">
                                <option value="todos">Todos</option><option value="inicio">Inicio · 5 Mbps</option><option value="conecta">Conecta · 8 Mbps</option><option value="avanza">Avanza · 10 Mbps</option><option value="turbo">Turbo · 20 Mbps</option><option value="sin_plan">Sin plan definido</option>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-deuda" class="mb-1.5 block text-xs font-semibold text-zinc-500">Deuda confirmada</label>
                            <select id="filtro-deuda" wire:model.live="filtroDeuda" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-700">
                                <option value="todos">Todos</option><option value="con_deuda">Con deuda</option><option value="sin_deuda">Sin deuda</option>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-revision" class="mb-1.5 block text-xs font-semibold text-zinc-500">Revisar inconsistencias</label>
                            <select id="filtro-revision" wire:model.live="filtroRevision" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2.5 text-sm dark:border-zinc-700">
                                <option value="todos">Sin filtro</option><option value="suspendido_facturando">Suspendidos con facturación habilitada</option><option value="activo_sin_facturar">Activos con facturación deshabilitada</option>
                            </select>
                        </div>
                    </div>
                @endif
                <p class="text-[11px] text-zinc-500">Todos los filtros se combinan. Las deudas son cargos confirmados con saldo, no apuntes históricos. Ningún filtro suspende servicios ni genera cargos.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-5">
                <div>
                    <h2 class="text-base font-bold">Lista de clientes</h2>
                    <p class="text-xs text-zinc-500">{{ $clientes->total() }} registros encontrados · página {{ $clientes->currentPage() }} de {{ $clientes->lastPage() }}</p>
                </div>
                <p class="text-xs text-zinc-500">{{ $totalUpina }} Upina · {{ $totalCalasuca }} Calasuca · {{ $totalSinSede }} sin sede</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-zinc-100 text-[11px] font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th class="px-3 py-3 sm:px-4">Código</th>
                            <th class="px-3 py-3 sm:px-4">Cliente</th>
                            <th class="px-3 py-3 sm:px-4">Sede</th>
                            <th class="px-3 py-3 sm:px-4">Tarifa / cobro</th>
                            <th class="px-3 py-3 sm:px-4">Internet</th>
                            <th class="px-3 py-3 sm:px-4">Deuda</th>
                            <th class="px-3 py-3 text-right sm:px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($clientes as $cliente)
                            <tr wire:key="cliente-{{ $cliente->id }}" class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                <td class="px-3 py-3 align-top sm:px-4"><span class="rounded-md bg-blue-500/10 px-2 py-1 text-xs font-bold text-blue-700 dark:text-blue-300">{{ $cliente->codigo }}</span></td>
                                <td class="px-3 py-3 align-top sm:px-4">
                                    <a href="{{ route('clientes.show', $cliente->id) }}" wire:navigate class="font-semibold hover:text-blue-600 dark:hover:text-blue-300">{{ $cliente->nombre }}</a>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $cliente->telefono ?: 'Sin celular' }}</p>
                                </td>
                                <td class="px-3 py-3 align-top sm:px-4">
                                    @if ($cliente->sede === 'upina')
                                        <span class="rounded-md bg-blue-500/10 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">Upina</span>
                                    @elseif ($cliente->sede === 'calasuca')
                                        <span class="rounded-md bg-indigo-500/10 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300">Calasuca</span>
                                    @else
                                        <span class="text-xs text-amber-700 dark:text-amber-300">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top sm:px-4">
                                    <p class="font-bold">{{ $cliente->tarifa_cobro !== null ? 'S/ '.number_format((float) $cliente->tarifa_cobro, 2) : 'Sin tarifa' }}</p>
                                    <p class="mt-1 text-[11px] {{ $cliente->cobro_habilitado ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-500' }}">{{ $cliente->cobro_habilitado ? 'Facturación habilitada' : 'No habilitada' }}</p>
                                </td>
                                <td class="px-3 py-3 align-top sm:px-4">
                                    @if ($cliente->fecha_baja)
                                        <span class="rounded-md bg-zinc-600 px-2 py-1 text-xs font-semibold text-white">Baja definitiva</span>
                                    @elseif ($cliente->servicio_tecnico_id && $cliente->estado_tecnico === 'suspendido')
                                        <span class="rounded-md bg-amber-500/10 px-2 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300">Suspendido</span>
                                    @elseif ($cliente->servicio_tecnico_id && $cliente->estado_tecnico === 'activo')
                                        <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Activo</span>
                                    @else
                                        <span class="text-xs text-zinc-500">Sin ficha técnica</span>
                                    @endif
                                    @if ($cliente->servicio_tecnico_id)
                                        <p class="mt-1 max-w-36 truncate text-[11px] text-zinc-500" title="{{ $cliente->plan_tecnico }}">{{ $cliente->plan_tecnico ?: 'Plan no definido' }}@if($cliente->velocidad_tecnica) · {{ $cliente->velocidad_tecnica }} Mbps @endif</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top sm:px-4">
                                    @if ($cliente->deuda_confirmada > 0)
                                        <span class="font-bold text-red-600 dark:text-red-400">S/ {{ number_format($cliente->deuda_confirmada, 2) }}</span>
                                    @else
                                        <span class="font-semibold text-emerald-700 dark:text-emerald-300">S/ 0.00</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right align-top sm:px-4">
                                    <div class="flex items-start justify-end gap-2">
                                        <a href="{{ route('clientes.show', $cliente->id) }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-bold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Ver ficha</a>
                                        <details class="relative text-left">
                                            <summary aria-label="Más acciones para {{ $cliente->nombre }}" class="cursor-pointer list-none rounded-lg border border-zinc-300 px-3 py-2 text-xs font-bold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">···</summary>
                                            <div class="absolute right-0 z-30 mt-1 w-44 rounded-xl border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                                <a href="{{ route('clientes.editar', $cliente->id) }}" wire:navigate class="block rounded-lg px-3 py-2 text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800">Editar datos</a>
                                                <button type="button" wire:click="abrirPerfil({{ $cliente->id }})" class="block w-full rounded-lg px-3 py-2 text-left text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800">Configurar facturación y servicio</button>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                            @if ($perfilClienteId === (int) $cliente->id)
                                <tr wire:key="perfil-unificado-{{ $cliente->id }}">
                                    <td colspan="7" class="bg-zinc-50 p-3 dark:bg-zinc-950/50 sm:p-5">
                                        <div class="mb-3 flex items-center justify-between gap-2">
                                            <h3 class="text-sm font-bold">Configuración · {{ $cliente->codigo }} — {{ $cliente->nombre }}</h3>
                                            <button type="button" wire:click="cerrarPerfil" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium dark:border-zinc-700">Cerrar</button>
                                        </div>
                                        @include('livewire.clientes.perfil-unificado')
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-zinc-500">No se encontraron clientes con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800 sm:p-5">{{ $clientes->links() }}</div>
        </div>
    @endif
</div>
