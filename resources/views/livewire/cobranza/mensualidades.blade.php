<div class="mx-auto max-w-7xl space-y-5 pb-12">
    <header class="rounded-3xl bg-gradient-to-r from-slate-950 via-slate-900 to-blue-900 p-5 text-white shadow-sm sm:p-7">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-200">Sistema ISP / Facturación y cobros</p>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Mensualidades</h1>
                <p class="mt-2 text-sm leading-6 text-blue-100">Consulta los cargos emitidos del mes y sus saldos. Las tarifas y la habilitación de cobro se configuran individualmente en el padrón.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('cobranza.index') }}" class="rounded-xl border border-white/30 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10">Cobros y deudas →</a>
                <button type="button" wire:click="cambiarVista('padron')" class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-blue-900 hover:bg-blue-50">Configurar tarifas</button>
            </div>
        </div>
    </header>

    @if (session()->has('mensaje'))
        <div role="status" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ session('mensaje') }}</div>
    @endif
    @error('mes')
        <div role="alert" class="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</div>
    @enderror

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap gap-2" role="group" aria-label="Vistas de mensualidades">
            <button type="button" wire:click="cambiarVista('periodo')" @class(['rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors', 'bg-blue-600 text-white' => $vista === 'periodo', 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200' => $vista !== 'periodo'])>Cargos del período</button>
            <button type="button" wire:click="cambiarVista('padron')" @class(['rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors', 'bg-blue-600 text-white' => $vista === 'padron', 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200' => $vista !== 'padron'])>Padrón y tarifas</button>
        </div>
        <label class="flex items-center gap-3 text-sm font-semibold">
            <span class="text-zinc-500 dark:text-zinc-400">Período</span>
            <input type="month" aria-label="Seleccionar período" wire:model.live="mes" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </label>
    </div>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Indicadores del período">
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/20">
            <p class="text-xs font-semibold text-blue-800 dark:text-blue-300">Período seleccionado</p>
            <p class="mt-2 text-xl font-extrabold">{{ \Carbon\Carbon::parse($mesSeleccionado . '-01')->locale('es')->translatedFormat('F Y') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Cargos emitidos, no ingresos recibidos</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Mensualidades emitidas</p>
            <p class="mt-2 text-2xl font-extrabold">{{ $resumenPeriodo['emitidos'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">De {{ $totalHabilitados }} facturaciones habilitadas hoy</p>
        </div>
        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Clientes con saldo del período</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-800 dark:text-amber-300">{{ $resumenPeriodo['clientesPendientes'] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Solo cargos ya emitidos</p>
        </div>
        <div class="rounded-2xl border border-red-300 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/20">
            <p class="text-xs font-semibold text-red-700 dark:text-red-300">Saldo pendiente del período</p>
            <p class="mt-2 text-2xl font-extrabold text-red-700 dark:text-red-300">S/ {{ number_format($resumenPeriodo['pendiente'], 2) }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Después de pagos aplicados a estos cargos</p>
        </div>
    </section>

    <section class="grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-3" aria-label="Balance de cargos mensuales">
        <div><p class="text-xs text-zinc-500">Facturado en el período</p><p class="mt-1 text-lg font-bold">S/ {{ number_format($resumenPeriodo['facturado'], 2) }}</p></div>
        <div><p class="text-xs text-zinc-500">Pagos aplicados a esos cargos</p><p class="mt-1 text-lg font-bold text-emerald-700 dark:text-emerald-400">S/ {{ number_format($resumenPeriodo['aplicado'], 2) }}</p></div>
        <div>
            <p class="text-xs text-zinc-500">Porcentaje aplicado</p>
            @php($avance = $resumenPeriodo['facturado'] > 0 ? min(100, round(100 * $resumenPeriodo['aplicado'] / $resumenPeriodo['facturado'])) : 0)
            <p class="mt-1 text-lg font-bold">{{ $avance }}%</p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $avance }}%"></div></div>
        </div>
    </section>

    @if ($vista === 'periodo')
        <details class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" aria-label="Administración de generación de cargos">
            <summary class="cursor-pointer text-sm font-bold">Generación del mes · Simular o completar cargos faltantes</summary>
            <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Operación masiva: afecta a todos los clientes con facturación habilitada para el período seleccionado. Primero simula. Los cargos ya emitidos para cada cliente y mes no se duplican.</p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" wire:click="simular" wire:loading.attr="disabled" wire:target="simular,generar" class="rounded-xl border border-blue-500 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950/30">Simular sin guardar</button>
                <button type="button" wire:click="generar" wire:confirm="¿Confirmas generar los cargos que falten para TODOS los clientes habilitados del período seleccionado?" wire:loading.attr="disabled" wire:target="simular,generar" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">Confirmar generación</button>
            </div>
            <p wire:loading wire:target="simular,generar" class="mt-2 text-xs font-medium text-blue-600">Procesando operación…</p>
        </details>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" aria-label="Mensualidades del período">
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-800 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-lg font-bold">Cargos y saldos del período</h2><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Consulta los saldos mensuales ya emitidos. Los cargos manuales y las deudas de equipos no aparecen aquí.</p></div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">{{ $resumenPeriodo['clientesPendientes'] }} clientes con saldo</span>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <label class="block sm:col-span-2 xl:col-span-2"><span class="mb-1 block text-xs font-semibold text-zinc-500">Buscar abonado</span><input type="search" wire:model.live.debounce.350ms="buscar" placeholder="Código, nombre o celular" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950" /></label>
                    <label class="block"><span class="mb-1 block text-xs font-semibold text-zinc-500">Localidad</span><select wire:model.live="filtroSede" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="todas">Todas</option>@foreach ($sedes as $sede)<option value="{{ $sede }}">{{ $sede }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1 block text-xs font-semibold text-zinc-500">Conexión</span><select wire:model.live="filtroConexion" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="todos">Todas</option><option value="activo">Activa</option><option value="suspendido">Suspendida</option><option value="sin_servicio">Sin servicio</option></select></label>
                    <label class="block"><span class="mb-1 block text-xs font-semibold text-zinc-500">Facturación actual</span><select wire:model.live="filtroFacturacion" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="todos">Todas</option><option value="habilitada">Habilitada</option><option value="deshabilitada">Deshabilitada</option></select></label>
                    <label class="block"><span class="mb-1 block text-xs font-semibold text-zinc-500">Saldo del cargo</span><select wire:model.live="filtroDeuda" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="pendientes">Pendientes</option><option value="pagados">Pagados</option><option value="todos">Todos</option></select></label>
                </div>
                <div class="mt-3 flex items-center justify-end"><button type="button" wire:click="limpiarFiltrosPeriodo" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-bold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Limpiar filtros</button></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-zinc-100 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300"><tr>
                        <th scope="col" class="px-4 py-3">Cliente</th><th scope="col" class="px-4 py-3">Localidad</th><th scope="col" class="px-4 py-3">Conexión</th><th scope="col" class="px-4 py-3">Facturación</th><th scope="col" class="px-4 py-3">Cargo</th><th scope="col" class="px-4 py-3">Pendiente</th><th scope="col" class="px-4 py-3 text-right">Acciones</th>
                    </tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($cargosPeriodo as $cargo)
                        <tr wire:key="cargo-periodo-{{ $cargo->mensualidad_id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3"><p class="font-bold">{{ $cargo->nombre }}</p><p class="text-xs text-zinc-500">{{ $cargo->codigo }} @if($cargo->telefono) · {{ $cargo->telefono }} @endif</p></td>
                            <td class="px-4 py-3">{{ $cargo->sede ?: 'Sin registrar' }}</td>
                            <td class="px-4 py-3">
                                @if ((int) $cargo->activos > 0)<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Activa</span>
                                @elseif ((int) $cargo->suspendidos > 0)<span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-950/50 dark:text-red-300">Suspendida</span>
                                @else<span class="text-xs text-zinc-500">Sin activa</span>@endif
                            </td>
                            <td class="px-4 py-3">@if ($cargo->habilitado)<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Habilitada</span>@else<span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Deshabilitada hoy</span>@endif</td>
                            <td class="px-4 py-3 font-semibold">S/ {{ number_format((float) $cargo->importe, 2) }}</td>
                            <td class="px-4 py-3 font-bold {{ (float) $cargo->saldo > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400' }}">S/ {{ number_format((float) $cargo->saldo, 2) }}</td>
                            <td class="px-4 py-3"><div class="flex justify-end gap-2"><a href="{{ route('cobranza.ficha', ['cliente' => $cargo->cliente_id]) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700">Ver / pagar</a><a href="{{ route('clientes.show', ['cliente' => $cargo->cliente_id]) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-xs font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Ficha</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center"><p class="font-semibold">No hay cargos mensuales que coincidan con estos filtros.</p><p class="mt-2 text-xs text-zinc-500">Si todavía no se emitieron mensualidades para este período, revisa el padrón, simula y confirma la generación cuando corresponda.</p></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $cargosPeriodo->links() }}</div>
        </section>
    @else
        <section class="grid gap-3 sm:grid-cols-3" aria-label="Resumen de la configuración actual">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs text-zinc-500">Clientes en el sistema</p><p class="mt-2 text-2xl font-bold">{{ $totalClientes }}</p></div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs text-zinc-500">Tarifas configuradas</p><p class="mt-2 text-2xl font-bold">{{ $totalConfiguraciones }}</p></div>
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/20"><p class="text-xs text-emerald-800 dark:text-emerald-300">Facturaciones habilitadas</p><p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $totalHabilitados }}</p></div>
        </section>
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" aria-label="Directorio de abonados">
        <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 dark:border-zinc-800 sm:p-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-bold">Padrón y tarifas</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Abre la ficha de cada cliente aquí mismo. Edita de forma individual. Guardar no modifica cargos ya emitidos.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-[170px_minmax(230px,1fr)]">
                <div>
                    <label for="filtro-isp" class="mb-1 block text-xs font-semibold text-zinc-500">Mostrar</label>
                    <select id="filtro-isp" wire:model.live="filtro" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="todos">Todos los clientes</option>
                        <option value="habilitados">Con cobro habilitado</option>
                        <option value="sin_configurar">Sin habilitar</option>
                    </select>
                </div>
                <div>
                    <label for="buscar-isp" class="mb-1 block text-xs font-semibold text-zinc-500">Buscar nombre o código</label>
                    <input id="buscar-isp" type="search" wire:model.live.debounce.300ms="buscar" placeholder="Ej. abctrans o CU122" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                </div>
            </div>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($clientes as $c)
                <article wire:key="mensualidad-cliente-{{ $c->id }}" class="p-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/30 sm:p-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1.35fr)_auto] lg:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="break-words text-base font-bold">{{ $c->nombre }}</p>
                                @if ($c->habilitado)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">Cobro habilitado</span>
                                @elseif ($c->tarifa_mensual !== null)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Sin habilitar</span>
                                @else
                                    <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Sin tarifa</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $c->codigo }} @if($c->telefono) · {{ $c->telefono }} @endif</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tarifa guardada</p>
                                <p class="mt-1 text-xl font-bold">{{ $c->tarifa_mensual !== null ? 'S/ '.number_format((float) $c->tarifa_mensual, 2) : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Inicio de cobro</p>
                                <p class="mt-1 text-sm font-semibold">{{ $c->inicio_cobro ? \Carbon\Carbon::parse($c->inicio_cobro)->format('d/m/Y') : 'Sin configurar' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <button type="button" wire:click="editar({{ $c->id }})" wire:loading.attr="disabled" wire:target="editar" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                                {{ $clienteId === (int) $c->id ? 'Editando cliente' : 'Revisar / editar' }}
                            </button>
                            <a href="{{ route('clientes.show', $c->id) }}" class="rounded-xl border border-zinc-300 px-4 py-3 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Ficha</a>
                        </div>
                    </div>

                    @if ($clienteId === (int) $c->id)
                        <div class="mt-5 rounded-2xl border border-blue-400/40 bg-blue-50/70 p-4 dark:bg-blue-950/20 sm:p-5">
                            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-blue-700 dark:text-blue-300">Revisión individual · {{ $c->codigo }}</p>
                                    <h3 class="mt-1 text-lg font-bold">{{ $clienteNombre }}</h3>
                                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Modifica únicamente a este cliente. Guardar no modifica las mensualidades ya emitidas.</p>
                                </div>
                                <button type="button" wire:click="cerrar" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">Cerrar</button>
                            </div>
                            <form wire:submit="guardar" class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="tarifa-manual-{{ $c->id }}" class="mb-1 block text-sm font-semibold">Mensualidad confirmada (S/)</label>
                                        <input id="tarifa-manual-{{ $c->id }}" type="number" min="0.01" step="0.01" required wire:model="tarifa" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-base dark:border-zinc-700 dark:bg-zinc-950" />
                                        @error('tarifa') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="inicio-manual-{{ $c->id }}" class="mb-1 block text-sm font-semibold">Empezar a cobrar desde</label>
                                        <input id="inicio-manual-{{ $c->id }}" type="date" required wire:model="inicioCobro" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-base dark:border-zinc-700 dark:bg-zinc-950" />
                                        @error('inicioCobro') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <label class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                                    <input type="checkbox" wire:model="habilitado" class="mt-1 h-5 w-5 rounded border-zinc-300" />
                                    <span>
                                        <strong class="block text-sm">Habilitar facturación mensual de este cliente</strong>
                                        <span class="mt-1 block text-xs leading-5 text-zinc-600 dark:text-zinc-400">Márcalo solo si confirmaste que corresponde cobrarle. Al entrar después a Cobranza pueden generarse automáticamente cargos pendientes de octubre para este cliente.</span>
                                    </span>
                                </label>
                                @error('habilitado') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" wire:loading.attr="disabled" wire:target="guardar" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50">Guardar este cliente</button>
                                    <button type="button" wire:click="cerrar" class="rounded-xl border border-zinc-300 px-4 py-3 text-sm font-semibold dark:border-zinc-700">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <p class="text-lg font-semibold">No encontramos clientes con ese filtro.</p>
                    <p class="mt-2 text-sm text-zinc-500">Prueba otro código, nombre o filtro.</p>
                </div>
            @endforelse
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800 sm:p-5">{{ $clientes->links() }}</div>
    </section>

    @endif

    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-900 dark:border-blue-900 dark:bg-blue-950/20 dark:text-blue-200">
        <strong>Importante:</strong> Mensualidades administra los cargos emitidos. Los ingresos reales, abonos y crédito a favor se revisan en <a href="{{ route('cobranza.index') }}" class="font-bold underline">Cobros y deudas</a>. No se ejecutan cortes de internet desde aquí.
    </div>
</div>
