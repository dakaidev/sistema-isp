<div class="mx-auto max-w-7xl space-y-6 pb-12">
    <header class="rounded-3xl bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-6 text-white shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold tracking-wider text-blue-100">SISTEMA ISP · ARCHIVO HISTÓRICO</span>
            <a href="{{ route('dashboard') }}" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-bold text-white hover:bg-white/10">← Panel principal</a>
        </div>
        <h1 class="mt-5 text-3xl font-bold sm:text-4xl">Historial de pagos del Excel</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-blue-100">Recuperamos los importes que anotaste mes a mes en tu Excel. Puedes buscar a cada cliente y revisar sus apuntes anteriores sin afectar cobros o pagos actuales.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('cobranza.index') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-blue-950 hover:bg-blue-100">Cobranza: registrar pago nuevo →</a>
            <a href="{{ route('cobranza.mensualidades') }}" class="rounded-xl border border-white/30 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">Configurar mensualidades</a>
        </div>
    </header>

    <section class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Apuntes mensuales recuperados</p>
            <p class="mt-2 text-3xl font-bold">{{ number_format($totalRegistros) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Incluye ceros explícitos en el Excel</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Importes positivos</p>
            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($registrosPositivos) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Pagos anotados, no necesariamente validados</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Con importe en el mes elegido</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $conImporteMes }}</p>
            <p class="mt-1 text-xs text-zinc-500">No equivale a "mensualidad al día"</p>
        </div>
    </section>

    <div class="rounded-2xl border border-amber-400/50 bg-amber-500/10 p-5 text-sm text-amber-900 dark:text-amber-200">
        <strong>Muy importante:</strong> cada columna tiene un mes, pero el Excel no trae el día exacto de pago ni especifica a qué facturas se aplicó. Una celda con cero no prueba una deuda; una celda con S/ 120 no prueba dos mensualidades completas. Este historial <strong>no descuenta saldos</strong> y no crea cargos, pagos nuevos ni cortes. El saldo antiguo del Excel se revisa por separado.
    </div>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
            <div>
                <h2 class="text-xl font-bold">Buscar en el historial antiguo</h2>
                <p class="mt-1 text-sm text-zinc-500">La última columna del Excel corresponde a septiembre de 2026, según el orden de sus encabezados.</p>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-[1fr_200px_200px]">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-zinc-500">Nombre, código o teléfono</label>
                    <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Busca, por ejemplo, CU101" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-zinc-500">Mes del Excel</label>
                    <select wire:model.live="mes" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @foreach($periodos as $p)
                            <option value="{{ $p['valor'] }}">{{ $p['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-zinc-500">Filtro</label>
                    <select wire:model.live="filtro" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="todos">Todos</option>
                        <option value="con_importe">Con importe positivo</option>
                        <option value="sin_importe">Sin importe positivo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse($clientes as $c)
                @php($apunte = $mesPorCliente->get($c->id))
                @php($referencia = $referencias->get($c->id))
                <article wire:key="historico-excel-{{ $c->id }}" class="p-4 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-bold">{{ $c->nombre }} <span class="ml-2 text-xs font-normal text-zinc-500">{{ $c->codigo }}</span></p>
                            <p class="mt-1 text-xs text-zinc-500">Estado Excel antiguo: {{ $referencia->estado_excel ?? 'Sin dato' }} · Deuda anotada al cierre: {{ isset($referencia->deuda_excel) ? 'S/ '.number_format((float)$referencia->deuda_excel, 2) : 'Sin dato' }} (sin verificar)</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <p class="text-xs text-zinc-500">Importe anotado en este mes</p>
                                @if($apunte)
                                    <p class="text-xl font-bold {{ (float)$apunte->importe_anotado > 0 ? 'text-emerald-600 dark:text-emerald-400' : '' }}">S/ {{ number_format((float)$apunte->importe_anotado,2) }}</p>
                                    <p class="text-xs text-zinc-500">Celda {{ $apunte->celda_excel }}</p>
                                @else
                                    <p class="mt-1 font-semibold text-zinc-400">Sin anotación</p>
                                @endif
                            </div>
                            <button type="button" wire:click="verCliente({{ $c->id }})" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">
                                {{ $clienteSeleccionado === $c->id ? 'Ocultar' : 'Ver historial completo' }}
                            </button>
                        </div>
                    </div>
                    @if($clienteSeleccionado === $c->id && $clienteDetalle)
                        <div class="mt-5 rounded-2xl border border-blue-300/40 bg-blue-50/40 p-4 dark:border-blue-900 dark:bg-blue-950/20">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h3 class="font-bold">{{ $clienteDetalle->nombre }} — todos los meses anotados</h3>
                                <a href="{{ route('cobranza.index') }}" class="rounded-xl border border-blue-400 px-3 py-2 text-sm font-bold text-blue-700 dark:text-blue-300">Ir a Cobranza →</a>
                            </div>
                            <p class="mb-3 text-xs text-zinc-500">Montos tal como estaban en Excel. La fecha es el mes de la columna, no el día real del abono.</p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @forelse($historialDetalle as $h)
                                    <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900">
                                        <div>
                                            <p class="text-sm font-semibold">{{ \Carbon\CarbonImmutable::parse($h->periodo)->locale('es')->translatedFormat('F Y') }}</p>
                                            <p class="text-xs text-zinc-500">{{ $h->celda_excel }}</p>
                                        </div>
                                        <p class="font-bold">S/ {{ number_format((float)$h->importe_anotado,2) }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-zinc-500">Este cliente no tiene apuntes mensuales en el Excel.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="p-10 text-center text-sm text-zinc-500">No encontramos clientes para esa búsqueda.</div>
            @endforelse
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $clientes->links() }}</div>
    </section>
</div>
