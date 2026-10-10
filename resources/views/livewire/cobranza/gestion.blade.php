<div class="mx-auto max-w-7xl space-y-6 pb-10">
    <div class="rounded-3xl bg-gradient-to-r from-slate-950 to-blue-900 p-7 text-white">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-200">Sistema ISP / Operaciones</p>
        <h1 class="mt-2 text-3xl font-bold">Cobranza y revisión de cortes</h1>
        <p class="mt-2 max-w-3xl text-sm text-blue-100">
            Consulta las deudas actuales, registra cargos y pagos completos o parciales.
            Los avisos de corte se basan únicamente en cargos confirmados; aquí NO se corta la conexión automáticamente.
        </p>
    </div>

    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-emerald-400/50 bg-emerald-950/40 p-4 text-sm font-semibold text-emerald-300">
            {{ session('mensaje') }}
        </div>
    @endif
    @error('operacion')
        <div class="rounded-xl border border-red-400/50 bg-red-950/40 p-4 text-sm text-red-300">{{ $message }}</div>
    @enderror

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Clientes en el padrón</p>
            <p class="mt-2 text-3xl font-bold">{{ \App\Models\Cliente::count() }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Mensualidades</p>
            <a href="{{ url('/cobranza/mensualidades') }}" class="mt-2 inline-block font-bold text-blue-500 hover:underline">Configurar clientes →</a>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Finanzas del negocio</p>
            <a href="{{ url('/finanzas') }}" class="mt-2 inline-block font-bold text-blue-500 hover:underline">Ver ingresos y gastos →</a>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-4 text-sm dark:text-amber-100">
        <strong>Nota:</strong> las deudas actuales son los cargos pendientes. Los apuntes importados se conservan en el historial para consulta y no crean cobros nuevos.
    </div>

    @if ($clienteSeleccionado !== null)
        <section id="panel-cobranza" class="scroll-mt-8 rounded-2xl border border-blue-500/50 bg-white p-6 shadow-sm dark:bg-zinc-900">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-500">{{ $nombreSeleccionado }}</p>
                    <h2 class="mt-2 text-xl font-bold">
                        @if ($accion === 'cargo') Registrar cargo comprobado
                        @elseif ($accion === 'pago') Registrar pago o adelanto
                        @else Historial de pagos y cargos @endif
                    </h2>
                </div>
                <button type="button" wire:click="cerrar" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">Cerrar</button>
            </div>

            @if ($accion !== 'historial')
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-semibold">¿A qué corresponde? (importante para NO cortar por deudas de equipos)</label>
                    <select wire:model="categoria" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="internet">Servicio de internet</option>
                        <option value="equipo">Router / equipo / préstamo cobrado</option>
                        <option value="otros">Otros conceptos</option>
                    </select>
                    <p class="mt-1 text-xs text-zinc-500">Los pagos se aplican solo a cargos de su misma categoría; una deuda de router nunca activa aviso de corte de internet.</p>
                </div>
            @endif

            @if ($accion === 'cargo')
                <p class="mb-4 text-sm text-zinc-500">Úsalo solo para una mensualidad u otro cobro cuyo importe y vencimiento estén comprobados. No crea servicios ni facturas automáticas.</p>
                <form wire:submit="guardarCargo" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Importe (S/)</label>
                            <input type="number" min="0.01" step="0.01" wire:model="importe" required class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                            @error('importe') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Vence el</label>
                            <input type="date" wire:model="fechaVencimiento" required class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                            @error('fechaVencimiento') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Concepto (ej. Internet octubre 2026)</label>
                        <input type="text" wire:model="concepto" required maxlength="255" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                        @error('concepto') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardarCargo" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">Registrar cargo confirmado</button>
                </form>
            @elseif ($accion === 'pago')
                <p class="mb-4 text-sm text-zinc-500">El pago se aplica primero a cargos verificados antiguos. Si sobra dinero o todavía no hay deuda verificada, queda como crédito a favor.</p>
                <form wire:submit="guardarPago" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Importe recibido (S/)</label>
                            <input type="number" min="0.01" step="0.01" wire:model="importe" required class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                            @error('importe') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Fecha de pago</label>
                            <input type="date" max="{{ today('America/Lima')->toDateString() }}" wire:model="fechaPago" required class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                            @error('fechaPago') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Medio de pago</label>
                            <select wire:model="medio" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="efectivo">Efectivo</option><option value="yape">Yape</option><option value="plin">Plin</option><option value="transferencia">Transferencia</option><option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Operación o referencia (opcional)</label>
                            <input type="text" maxlength="100" wire:model="referenciaPago" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                            @error('referenciaPago') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Observaciones (opcional)</label>
                            <input type="text" wire:model="observaciones" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700">
                            @error('observaciones') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardarPago" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50">Registrar pago recibido</button>
                </form>
            @elseif ($accion === 'historial')
                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-3 font-bold">Últimos pagos registrados</h3>
                        @forelse ($historialPagos as $p)
                            <div class="mb-2 rounded-xl bg-zinc-100 p-3 text-sm dark:bg-zinc-800">
                                <strong>S/ {{ number_format((float) $p->monto, 2) }}</strong> ({{ $p->categoria }})
                                · {{ $p->fecha_pago }} · {{ strtoupper($p->medio) }}
                                @if ($p->referencia) <div class="text-xs text-zinc-500">Operación: {{ $p->referencia }}</div> @endif
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">Todavía no se registraron pagos en el sistema.</p>
                        @endforelse
                    </div>
                    <div>
                        <h3 class="mb-3 font-bold">Últimos cargos verificados</h3>
                        @forelse ($historialCargos as $c)
                            <div class="mb-2 rounded-xl bg-zinc-100 p-3 text-sm dark:bg-zinc-800">
                                <strong>S/ {{ number_format((float) $c->monto, 2) }}</strong> ({{ $c->categoria }})
                                · Vencimiento {{ $c->fecha_vencimiento }}
                                <div class="text-xs text-zinc-500">{{ $c->descripcion }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">Todavía no existen cargos verificados.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>
    @endif

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
            <div>
                <h2 class="text-xl font-bold">Clientes y cobranza</h2>
                <p class="mt-1 text-xs text-zinc-500">Muestra 15 por página. “Saldo verificado” es el importe confirmado pendiente de aplicar pagos.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="filtro" class="rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="todos">Todos los clientes</option>
                    
                    <option value="vencidos">Vencidos confirmados: revisar corte</option>
                    <option value="pagos">Con pagos registrados</option>
                </select>
                <input type="search" wire:model.live.debounce.350ms="buscar" placeholder="Buscar código, nombre o celular..." class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm sm:w-80 dark:border-zinc-700">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-sm">
                <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <tr>
                        <th class="p-3">Cliente</th>
                        
                        
                        <th class="p-3">Saldo confirmado (total)</th>
                        <th class="p-3">Internet vencido verificado</th>
                        <th class="p-3">Crédito</th>
                        <th class="p-3">Último pago en sistema</th>
                        <th class="p-3">Situación</th>
                        <th class="p-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($clientes as $cliente)
                        @php
                            $r = $informacion[$cliente->id]['referencia'];
                            $balance = $informacion[$cliente->id]['resumen'];
                            $ultimoPago = $informacion[$cliente->id]['ultimo_pago'];
                        @endphp
                        <tr wire:key="cobranza-cliente-{{ $cliente->id }}" class="align-top hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="p-3">
                                <div class="font-semibold">{{ $cliente->nombre }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ $cliente->codigo }} · {{ $cliente->telefono ?: 'Sin celular' }}</div>
                            </td>
                            <td class="p-3 font-semibold">
                                S/ {{ number_format($balance['saldo'], 2) }}
                                <div class="mt-1 text-xs font-normal text-zinc-500">Internet S/ {{ number_format($balance['saldo_internet'], 2) }}</div>
                                <div class="text-xs font-normal text-zinc-500">Equipo S/ {{ number_format($balance['saldo_equipo'], 2) }}</div>
                            </td>
                            <td class="p-3 font-bold {{ $balance['vencido'] > 0 ? 'text-red-500' : '' }}">S/ {{ number_format($balance['vencido'], 2) }}</td>
                            <td class="p-3">S/ {{ number_format($balance['credito'], 2) }}</td>
                            <td class="p-3">
                                @if ($ultimoPago)
                                    <strong>S/ {{ number_format((float) $ultimoPago->monto, 2) }}</strong>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $ultimoPago->fecha_pago }} · {{ $ultimoPago->categoria }}</p>
                                @else
                                    <span class="text-xs text-zinc-500">Sin pago registrado</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($balance['vencido'] > 0)
                                    <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-700">Revisar para corte</span>
                                @elseif ($balance['saldo'] > 0)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Saldo pendiente</span>
                                @elseif ($balance['cargos'] > 0)
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">Cargos cubiertos</span>
                                @else
                                    <span class="text-xs text-zinc-500">Sin cargos verificados</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="abrir({{ $cliente->id }}, 'cargo')" x-on:click="window.scrollTo({top:0,behavior:'smooth'})" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white">Cargo</button>
                                    <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Pagar en ficha</a>
                                    <button type="button" wire:click="abrir({{ $cliente->id }}, 'historial')" x-on:click="window.scrollTo({top:0,behavior:'smooth'})" class="rounded-lg border border-zinc-400 px-3 py-2 text-xs font-semibold">Historial</button>
                                    
                                    <a href="{{ route('cobranza.ficha', $cliente->id) }}"
                                    class="rounded-lg bg-violet-700 px-3 py-2 text-xs font-bold text-white">
                                        Ficha completa
                                    </a>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-10 text-center text-zinc-500">No se encontraron clientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-5 dark:border-zinc-800">{{ $clientes->links() }}</div>
    </div>
</div>
