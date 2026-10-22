<div class="mx-auto w-full max-w-7xl space-y-6 pb-12">
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 to-blue-900 p-7 text-white">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-200">Sistema ISP / Infraestructura</p>
        <h1 class="mt-3 text-3xl font-bold">Cajas NAP y puertos</h1>
        <p class="mt-2 text-sm text-blue-100">Registra las cajas de Upina y Calasuca por separado. Las sedes tienen servidores independientes.</p>
        <p class="mt-2 text-xs text-blue-200">Fase 1: inventario físico y estados revisados. La vinculación a clientes e instalaciones se hará después; no se crean conexiones ni cobros.</p>
    </div>

    @if (session()->has('nap_mensaje'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('nap_mensaje') }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/20">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Cajas registradas en Upina</p>
            <p class="mt-1 text-3xl font-bold text-blue-800 dark:text-blue-300">{{ $totalUpina }}</p>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-900 dark:bg-indigo-950/20">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">Cajas registradas en Calasuca</p>
            <p class="mt-1 text-3xl font-bold text-indigo-800 dark:text-indigo-300">{{ $totalCalasuca }}</p>
        </div>
    </div>

    <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-5 dark:border-zinc-800">
            <div>
                <h2 class="text-xl font-bold">{{ $cajaEditandoId ? 'Editar caja NAP' : 'Registrar caja NAP' }}</h2>
                <p class="mt-1 text-sm text-zinc-500">No hace falta recordar todas tus cajas. Agrégalas cuando verifiques sus códigos.</p>
            </div>
            @if ($cajaEditandoId)
                <button type="button" wire:click="nuevaCaja" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">Cancelar edición</button>
            @endif
        </div>

        <form wire:submit="guardarCaja" class="space-y-5 p-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="nap-sede" class="mb-2 block text-sm font-semibold">Sede / servidor *</label>
                    <select id="nap-sede" wire:model="sede" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="upina">Upina</option>
                        <option value="calasuca">Calasuca</option>
                    </select>
                    @error('sede') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nap-codigo" class="mb-2 block text-sm font-semibold">Código / nombre de la caja *</label>
                    <input id="nap-codigo" wire:model="codigo" maxlength="80" placeholder="Ej.: ULTRA 05" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    @error('codigo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nap-capacidad" class="mb-2 block text-sm font-semibold">Capacidad *</label>
                    <select id="nap-capacidad" wire:model="capacidad" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="8">8 puertos</option>
                        <option value="16">16 puertos</option>
                    </select>
                    @error('capacidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nap-gps" class="mb-2 block text-sm font-semibold">Ubicación GPS (opcional)</label>
                    <input id="nap-gps" wire:model="ubicacionGps" maxlength="100" placeholder="latitud, longitud" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    @error('ubicacionGps') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nap-referencia" class="mb-2 block text-sm font-semibold">Referencia de ubicación</label>
                    <input id="nap-referencia" wire:model="referencia" maxlength="255" placeholder="Poste, calle o referencia" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    @error('referencia') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center pt-8">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium">
                        <input type="checkbox" wire:model="activa" class="size-4 rounded border-zinc-300" />
                        Caja en funcionamiento (registro activo)
                    </label>
                    @error('activa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="nap-observaciones" class="mb-2 block text-sm font-semibold">Observaciones</label>
                <textarea id="nap-observaciones" wire:model="observaciones" rows="2" maxlength="3000" placeholder="Información de campo para revisar más tarde" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                @error('observaciones') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="guardarCaja" class="rounded-xl bg-blue-700 px-6 py-3 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50">
                    {{ $cajaEditandoId ? 'Guardar cambios' : 'Registrar caja y generar puertos' }}
                </button>
                <p class="text-xs text-zinc-500">Los puertos nuevos comenzarán como <strong>Sin verificar</strong>. No se asignan clientes automáticamente.</p>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-4 p-5">
            <div class="min-w-52 flex-1">
                <label for="nap-buscar" class="mb-1 block text-sm font-semibold">Buscar caja o referencia</label>
                <input id="nap-buscar" wire:model.live.debounce.350ms="buscar" placeholder="Código ULTRA, calle, poste..." class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
            </div>
            <div class="min-w-44">
                <label for="nap-filtrar" class="mb-1 block text-sm font-semibold">Filtrar por sede</label>
                <select id="nap-filtrar" wire:model.live="filtroSede" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="todas">Todas las sedes</option>
                    <option value="upina">Upina</option>
                    <option value="calasuca">Calasuca</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="p-4">Código</th><th class="p-4">Sede</th><th class="p-4">Capacidad</th><th class="p-4">Referencia / GPS</th><th class="p-4">Registro</th><th class="p-4">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($cajas as $caja)
                        <tr wire:key="caja-nap-{{ $caja->id }}">
                            <td class="p-4 font-bold">{{ $caja->codigo }}</td>
                            <td class="p-4">{{ $caja->sede === 'upina' ? 'Upina' : 'Calasuca' }}</td>
                            <td class="p-4">{{ $caja->puertos_count }} / {{ $caja->capacidad }} puertos</td>
                            <td class="p-4">
                                {{ $caja->referencia ?: 'Sin referencia' }}
                                @if ($caja->latitud !== null && $caja->longitud !== null)
                                    <a href="https://www.google.com/maps?q={{ $caja->latitud }},{{ $caja->longitud }}" target="_blank" rel="noopener noreferrer" class="ml-2 text-blue-700 underline dark:text-blue-300">Ver GPS</a>
                                @endif
                            </td>
                            <td class="p-4">{{ $caja->activa ? 'Activa' : 'Inactiva' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="seleccionarCaja({{ $caja->id }})" class="rounded-lg bg-blue-100 px-3 py-2 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">Ver puertos</button>
                                    <button type="button" wire:click="editarCaja({{ $caja->id }})" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold dark:border-zinc-700">Editar caja</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-sm text-zinc-500">Todavía no hay cajas registradas con este filtro. Puedes añadirlas a medida que las verifiques.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $cajas->links() }}</div>
    </section>

    @if ($seleccionada)
        <section class="space-y-5 rounded-2xl border border-blue-300 bg-white p-5 shadow-sm dark:border-blue-900 dark:bg-zinc-900" id="detalle-caja-nap">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">{{ $seleccionada->codigo }} — {{ $seleccionada->sede === 'upina' ? 'Upina' : 'Calasuca' }}</h2>
                    <p class="text-sm text-zinc-500">{{ $seleccionada->capacidad }} puertos. El estado refleja solo lo revisado y anotado en el sistema.</p>
                </div>
                <button type="button" wire:click="$set('cajaSeleccionadaId', null)" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Cerrar detalle</button>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8">
                @foreach ($seleccionada->puertos as $puerto)
                    @php
                        $estilos = match ($puerto->estado) {
                            'disponible' => 'border-emerald-400 bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
                            'reservado' => 'border-blue-400 bg-blue-50 text-blue-900 dark:bg-blue-950 dark:text-blue-200',
                            'ocupado' => 'border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
                            'fuera_de_servicio' => 'border-zinc-500 bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200',
                            default => 'border-amber-400 bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
                        };
                        $nombreEstado = match ($puerto->estado) {
                            'disponible' => 'Disponible', 'reservado' => 'Reservado', 'ocupado' => 'Ocupado',
                            'fuera_de_servicio' => 'Sin servicio', default => 'Sin verificar',
                        };
                    @endphp
                    <button type="button" wire:key="nap-puerto-{{ $puerto->id }}" wire:click="abrirPuerto({{ $puerto->id }})" class="min-h-20 rounded-xl border-2 p-3 text-center transition hover:shadow-md {{ $estilos }}">
                        <span class="block text-lg font-bold">P{{ $puerto->numero }}</span>
                        <span class="mt-1 block text-xs">{{ $nombreEstado }}</span>
                    </button>
                @endforeach
            </div>

            @if ($puertoEditandoId !== null)
                <form wire:submit="guardarPuerto" class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <h3 class="font-bold">Actualizar estado del puerto</h3>
                    <p class="text-xs text-zinc-500">La ocupación todavía se registra manualmente. Vincular el cliente exacto será parte de la siguiente fase.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="nap-estado-puerto" class="mb-1 block text-sm font-semibold">Estado verificado</label>
                            <select id="nap-estado-puerto" wire:model="estadoPuerto" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="sin_verificar">Sin verificar</option>
                                <option value="disponible">Disponible</option>
                                <option value="reservado">Reservado</option>
                                <option value="ocupado">Ocupado (cliente por identificar)</option>
                                <option value="fuera_de_servicio">Fuera de servicio</option>
                            </select>
                            @error('estadoPuerto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="nap-puerto-notas" class="mb-1 block text-sm font-semibold">Nota de revisión</label>
                            <input id="nap-puerto-notas" wire:model="observacionesPuerto" maxlength="1500" placeholder="Ej.: pendiente identificar conexión" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                            @error('observacionesPuerto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2 text-sm font-bold text-white" wire:loading.attr="disabled" wire:target="guardarPuerto">Guardar estado</button>
                        <button type="button" wire:click="cancelarPuerto" class="rounded-xl border border-zinc-300 px-5 py-2 text-sm dark:border-zinc-700">Cancelar</button>
                    </div>
                </form>
            @endif

            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <h3 class="mb-2 font-bold">Últimos movimientos de esta caja</h3>
                <div class="max-h-72 space-y-2 overflow-y-auto">
                    @forelse ($seleccionada->movimientos as $movimiento)
                        <div class="flex flex-wrap justify-between gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-xs dark:bg-zinc-800">
                            <span>{{ $movimiento->detalle }}</span>
                            <span class="shrink-0 text-zinc-500">{{ $movimiento->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Sin movimientos registrados.</p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</div>
