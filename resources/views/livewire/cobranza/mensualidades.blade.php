<div class="mx-auto max-w-7xl space-y-6 pb-12">
    {{-- Panel de mensualidades manual: solo modifica la presentación, reutiliza el componente Livewire instalado. --}}
    <header class="overflow-hidden rounded-3xl bg-gradient-to-r from-slate-950 via-slate-900 to-blue-900 p-6 text-white shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-blue-100">Sistema ISP · Trabajo manual</span>
            <a href="{{ route('cobranza.index') }}" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">← Volver a Cobranza</a>
        </div>
        <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">Mensualidades por cliente</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-blue-100">Revisa cada abonado, confirma su tarifa y decide si corresponde facturarle. Sin importaciones masivas ni activaciones en bloque.</p>
    </header>

    @if (session()->has('mensaje'))
        <div role="status" class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-5 py-4 text-sm font-medium text-emerald-700 dark:text-emerald-300">
            {{ session('mensaje') }}
        </div>
    @endif

    <details open class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <summary class="cursor-pointer text-base font-bold">Generar mensualidades del mes (opción de administración)</summary>
        <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Esta operación afecta a TODOS los clientes habilitados. Primero revisa sus tarifas y ejecuta la simulación. Los cargos que ya existen para el mismo período no se vuelven a crear.</p>
        <div class="mt-4 flex flex-wrap items-end gap-3">
            <div>
                <label for="mes-manual" class="mb-1 block text-sm font-semibold">Mes a facturar</label>
                <input id="mes-manual" type="month" wire:model="mes" class="rounded-xl border border-zinc-300 bg-white px-3 py-3 dark:border-zinc-700 dark:bg-zinc-950" />
            </div>
            <button type="button" wire:click="simular" wire:loading.attr="disabled" wire:target="simular,generar" class="rounded-xl border border-blue-500 px-4 py-3 text-sm font-bold text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/30">Simular sin guardar</button>
            <button type="button" wire:click="generar" wire:confirm="¿Confirmas generar las mensualidades para TODOS los clientes habilitados del mes elegido?" wire:loading.attr="disabled" wire:target="simular,generar" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">Confirmar y generar mes</button>
        </div>
        @error('mes') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </details>

    <section class="grid gap-3 sm:grid-cols-3" aria-label="Resumen del padrón">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Clientes en la base</p>
            <p class="mt-2 text-3xl font-bold">{{ \App\Models\Cliente::count() }}</p>
            <p class="mt-1 text-xs text-zinc-500">Incluye clientes de prueba</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Con tarifa guardada</p>
            <p class="mt-2 text-3xl font-bold">{{ $totalConfiguraciones }}</p>
            <p class="mt-1 text-xs text-zinc-500">No significa que esté verificada</p>
        </div>
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/20">
            <p class="text-sm text-emerald-800 dark:text-emerald-300">Facturación habilitada</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ $totalHabilitados }}</p>
            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Solo clientes activados individualmente</p>
        </div>
    </section>

    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/20 sm:p-5">
        <h2 class="font-bold text-blue-900 dark:text-blue-200">Cómo trabajar cliente por cliente</h2>
        <p class="mt-2 text-sm leading-6 text-blue-900/80 dark:text-blue-100/80">
            <strong>1.</strong> Busca al abonado. <strong>2.</strong> Presiona <strong>Revisar / editar</strong>.
            <strong>3.</strong> Corrige su mensualidad e inicio, marca <strong>Habilitar cobro</strong> solo si corresponde y guarda.
            <strong>4.</strong> Ve a <strong>Cobranza</strong> para revisar deudas o registrar pagos.
        </p>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" aria-label="Directorio de abonados">
        <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 dark:border-zinc-800 sm:p-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-bold">Padrón y tarifas</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Abre la ficha de cada cliente aquí mismo. No necesitas desplazarte a otro formulario.</p>
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

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900" aria-label="Acciones de cobranza">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">Deudas, pagos e historial</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">En Cobranza puedes buscar al abonado y registrar pagos completos, parciales o revisar su saldo.</p>
            </div>
            <a href="{{ route('cobranza.index') }}" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">Ir a Cobranza →</a>
        </div>
    </section>

    
</div>
