<div class="mx-auto max-w-7xl space-y-5 pb-12">
    {{-- Mejora 06: cobranza operativa centralizada, sin alterar la lógica de cargos/pagos. --}}
    <header class="rounded-3xl border border-blue-800/40 bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-5 text-white shadow-sm sm:p-7">
        <p class="text-xs font-bold uppercase tracking-wider text-blue-200">Sistema ISP / Facturación y cobros</p>
        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Cobros y deudas</h1>
                <p class="mt-2 text-sm leading-6 text-blue-100">Un solo lugar para consultar deuda confirmada, registrar pagos y revisar posibles cortes. Los cargos mensuales se administran en Gestión mensual.</p>
            </div>
            <a href="{{ route('cobranza.mensualidades') }}" class="inline-flex shrink-0 items-center justify-between gap-4 rounded-2xl border border-blue-400/50 bg-blue-600/25 px-5 py-4 text-sm font-bold text-white hover:bg-blue-600/40">
                <span><span class="block text-base">Gestión mensual</span><span class="mt-1 block text-xs font-normal text-blue-100">Generar cargos · configurar tarifas</span></span>
                <span aria-hidden="true" class="text-xl">→</span>
            </a>
        </div>
    </header>

    @if (session()->has('mensaje'))
        <div role="status" class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ session('mensaje') }}</div>
    @endif
    @if (session()->has('advertencia_facturacion'))
        <div role="alert" class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-200">{{ session('advertencia_facturacion') }}</div>
    @endif
    @error('operacion')
        <div role="alert" class="rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300">{{ $message }}</div>
    @enderror

    {{-- Los valores se obtienen de MySQL. Cobrado = pagos recibidos durante el mes seleccionado. --}}
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores de cobranza">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Clientes con deuda confirmada</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-red-600 dark:text-red-400">{{ $clientesConDeuda }}</p>
            <p class="mt-1 text-xs text-zinc-500">De {{ $totalClientes }} clientes · cualquier categoría</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Pendiente total confirmado</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums">S/ {{ number_format($pendienteTotal, 2) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Cargos menos pagos aplicados</p>
        </div>
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50 p-4 dark:bg-emerald-950/15">
            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">Cobrado en {{ \Carbon\Carbon::createFromFormat('!Y-m', $mesSeleccionado)->locale('es')->translatedFormat('F Y') }}</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700 dark:text-emerald-300">S/ {{ number_format($cobradoMes, 2) }}</p>
            <p class="mt-1 text-xs text-emerald-800/70 dark:text-emerald-300/70">Pagos registrados en ese mes</p>
        </div>
        <div class="rounded-2xl border border-amber-500/30 bg-amber-50 p-4 dark:bg-amber-950/15">
            <p class="text-xs font-semibold text-amber-900 dark:text-amber-300">Clientes por revisar</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-800 dark:text-amber-300">{{ $porRevisar }}</p>
            <p class="mt-1 text-xs text-amber-900/70 dark:text-amber-300/70">Internet vencido confirmado; sin corte automático</p>
        </div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-5" aria-label="Buscar y filtrar clientes">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="block xl:col-span-2">
                <span class="mb-1 block text-xs font-semibold text-zinc-500 dark:text-zinc-400">Buscar cliente</span>
                <input type="search" wire:model.live.debounce.350ms="buscar" placeholder="Código, nombre, celular o dirección" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-zinc-500 dark:text-zinc-400">Localidad</span>
                <select wire:model.live="filtroSede" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="todas">Todas las localidades</option>
                    @foreach ($sedes as $sede) <option value="{{ $sede }}">{{ $sede }}</option> @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-zinc-500 dark:text-zinc-400">Conexión</span>
                <select wire:model.live="filtroConexion" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="todos">Todas las conexiones</option><option value="activo">Activa</option><option value="suspendido">Suspendida</option><option value="sin_servicio">Sin servicio</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-zinc-500 dark:text-zinc-400">Facturación actual</span>
                <select wire:model.live="filtroFacturacion" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="todos">Todas</option><option value="habilitada">Habilitada</option><option value="deshabilitada">Deshabilitada / sin configurar</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-zinc-500 dark:text-zinc-400">Deuda</span>
                <select wire:model.live="filtroDeuda" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="todos">Todos</option><option value="con_deuda">Con deuda</option><option value="al_dia">Al día con cargos</option><option value="vencido">Internet vencido</option><option value="sin_cargos">Sin cargos</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-zinc-500 dark:text-zinc-400">Período para cargos y cobros</span>
                <input type="month" wire:model.live="mes" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            </label>
            <div class="flex items-end">
                <button type="button" wire:click="limpiarFiltros" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Limpiar filtros</button>
            </div>
        </div>
        <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">El saldo total considera todos los cargos confirmados, no solo el mes seleccionado. Cambiar el período actualiza únicamente “Cargo del mes” y “Cobrado”.</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" aria-label="Clientes y estado de cobranza">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800 sm:p-5">
            <div>
                <h2 class="text-lg font-bold">Clientes y estado de cobranza</h2>
                <p class="mt-1 text-xs text-zinc-500">{{ $clientes->total() }} resultados · Deuda basada en cargos y pagos aplicados</p>
            </div>
            <a href="{{ route('cobranza.mensualidades') }}" class="rounded-xl border border-blue-500 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950/40">Revisar gestión mensual →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[990px] text-left text-sm">
                <thead class="bg-zinc-100 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th scope="col" class="px-4 py-3">Cliente</th>
                        <th scope="col" class="px-4 py-3">Deuda total</th>
                        <th scope="col" class="px-4 py-3">Cargo del mes</th>
                        <th scope="col" class="px-4 py-3">Último pago</th>
                        <th scope="col" class="px-4 py-3">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($clientes as $cliente)
                        @php
                            $ultimoPago = $ultimosPagos->get($cliente->id);
                            $deuda = (float) $cliente->deuda_total;
                            $vencido = (float) $cliente->internet_vencido;
                        @endphp
                        <tr wire:key="cobro-{{ $cliente->id }}" class="align-middle hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3">
                                <div class="font-bold">{{ $cliente->nombre }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ $cliente->codigo }} · {{ $cliente->sede ?: 'Sin localidad' }} @if ($cliente->telefono) · {{ $cliente->telefono }} @endif</div>
                            </td>
                            <td class="px-4 py-3 font-bold tabular-nums {{ $deuda > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400' }}">S/ {{ number_format($deuda, 2) }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums">S/ {{ number_format((float) $cliente->cargo_mes, 2) }}</td>
                            <td class="px-4 py-3">
                                @if ($ultimoPago)
                                    <p class="font-semibold tabular-nums">S/ {{ number_format((float) $ultimoPago->monto, 2) }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ \Carbon\Carbon::parse($ultimoPago->fecha_pago)->format('d/m/Y') }}</p>
                                @else
                                    <span class="text-xs text-zinc-500">Sin pagos registrados</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($vencido > 0)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">Revisar vencido</span>
                                @elseif ($deuda > 0)
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700 dark:bg-red-950/40 dark:text-red-300">Con deuda</span>
                                @elseif ((int) $cliente->cantidad_cargos > 0)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Al día</span>
                                @else
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">Sin cargos</span>
                                @endif
                                <p class="mt-1 text-xs text-zinc-500">
                                    @if ((int) $cliente->servicios_activos > 0) Conexión activa
                                    @elseif ((int) $cliente->servicios_suspendidos > 0) Conexión suspendida
                                    @else Sin conexión documentada @endif
                                    · {{ (bool) $cliente->facturacion_habilitada ? 'Facturación habilitada' : 'Facturación deshabilitada' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('cobranza.ficha', ['cliente' => $cliente->id]) }}" class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700">Cobrar</a>
                                    <a href="{{ route('clientes.show', ['cliente' => $cliente->id]) }}" class="shrink-0 rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Ficha</a>
                                    <button type="button" wire:click="abrir({{ $cliente->id }}, 'historial')" class="shrink-0 rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Historial</button>
                                    <button type="button" wire:click="abrir({{ $cliente->id }}, 'cargo')" title="Registrar cargo manual confirmado" aria-label="Cargo manual para {{ $cliente->nombre }}" class="shrink-0 rounded-lg border border-zinc-300 px-3 py-2 text-xs font-bold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">+</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center"><p class="font-semibold">Ningún cliente coincide con los filtros.</p><p class="mt-1 text-xs text-zinc-500">Puedes limpiar filtros o buscar por código y nombre.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $clientes->links() }}</div>
    </section>

    <p class="text-xs text-zinc-500">Los avisos de revisión no suspenden conexiones ni modifican facturación. Los pagos se registran desde la ficha de cobranza para conservar su distribución por meses.</p>

    {{-- Historial y cargo manual permanecen disponibles, pero fuera de la tabla principal. --}}
    @if ($clienteSeleccionado !== null)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/75 p-3 pt-10 sm:p-6" role="presentation">
            <section role="dialog" aria-modal="true" aria-label="Detalle de cobranza" class="w-full max-w-3xl rounded-2xl border border-zinc-300 bg-white p-5 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">{{ $nombreSeleccionado }}</p>
                        <h2 class="mt-2 text-xl font-bold">{{ $accion === 'cargo' ? 'Cargo manual confirmado' : 'Historial de cargos y pagos' }}</h2>
                    </div>
                    <button type="button" wire:click="cerrar" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-bold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Cerrar ×</button>
                </div>

                @if ($accion === 'cargo')
                    <p class="mt-4 text-sm text-zinc-500">Solo para cargos comprobados y excepcionales. Las mensualidades automáticas se administran desde Gestión mensual.</p>
                    <form wire:submit="guardarCargo" class="mt-4 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="mb-1 block text-sm font-semibold">Importe (S/)</span><input type="number" min="0.01" step="0.01" wire:model="importe" required class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700" />@error('importe') <span class="text-xs text-red-500">{{ $message }}</span> @enderror</label>
                            <label class="block"><span class="mb-1 block text-sm font-semibold">Fecha de vencimiento</span><input type="date" wire:model="fechaVencimiento" required class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700" />@error('fechaVencimiento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror</label>
                        </div>
                        <label class="block"><span class="mb-1 block text-sm font-semibold">Categoría</span><select wire:model="categoria" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950"><option value="internet">Internet</option><option value="equipo">Equipo / router</option><option value="otros">Otros</option></select>@error('categoria') <span class="text-xs text-red-500">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="mb-1 block text-sm font-semibold">Concepto</span><input type="text" wire:model="concepto" maxlength="255" required placeholder="Ej. Cargo manual verificado" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700" />@error('concepto') <span class="text-xs text-red-500">{{ $message }}</span> @enderror</label>
                        <button type="submit" wire:loading.attr="disabled" wire:target="guardarCargo" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">Registrar cargo verificado</button>
                    </form>
                @elseif ($accion === 'historial')
                    <div class="mt-5 grid gap-6 md:grid-cols-2">
                        <div>
                            <h3 class="mb-3 font-bold">Últimos pagos</h3>
                            @forelse ($historialPagos as $p)
                                <div class="mb-2 rounded-xl bg-zinc-100 p-3 text-sm dark:bg-zinc-800">
                                    <strong>S/ {{ number_format((float) $p->monto, 2) }}</strong> · {{ $p->fecha_pago }}
                                    <p class="mt-1 text-xs text-zinc-500">{{ ucfirst($p->categoria) }} · {{ strtoupper($p->medio) }}</p>
                                    @if ($p->referencia) <p class="text-xs text-zinc-500">Referencia: {{ $p->referencia }}</p> @endif
                                </div>
                            @empty <p class="text-sm text-zinc-500">Sin pagos registrados.</p> @endforelse
                        </div>
                        <div>
                            <h3 class="mb-3 font-bold">Últimos cargos</h3>
                            @forelse ($historialCargos as $c)
                                <div class="mb-2 rounded-xl bg-zinc-100 p-3 text-sm dark:bg-zinc-800">
                                    <strong>S/ {{ number_format((float) $c->monto, 2) }}</strong> · {{ $c->fecha_vencimiento }}
                                    <p class="mt-1 text-xs text-zinc-500">{{ ucfirst($c->categoria) }} · {{ $c->descripcion }}</p>
                                </div>
                            @empty <p class="text-sm text-zinc-500">Sin cargos confirmados.</p> @endforelse
                        </div>
                    </div>
                    <a href="{{ route('cobranza.ficha', ['cliente' => $clienteSeleccionado]) }}" class="mt-5 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Abrir ficha de cobranza completa →</a>
                @endif
            </section>
        </div>
    @endif
</div>
