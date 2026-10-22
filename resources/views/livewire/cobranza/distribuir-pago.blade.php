<div class="mx-auto max-w-5xl space-y-5 pb-12">
    <section class="rounded-3xl bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-7 text-white">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-200">Sistema ISP / Distribución por períodos</p>
        <h1 class="mt-3 text-3xl font-extrabold">{{ $pago->cliente_nombre }}</h1>
        <p class="mt-2 text-sm text-blue-100">{{ $pago->cliente_codigo }} · Pago #{{ $pago->id }} · Fecha de ingreso: {{ $pago->fecha_pago }}</p>
        <a class="mt-5 inline-block rounded-xl border border-white/30 px-4 py-2 text-sm font-semibold text-white" href="{{ route('cobranza.ficha', $pago->cliente_id) }}">← Volver a la ficha</a>
    </section>
    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-emerald-500/40 p-4 text-sm text-emerald-300">{{ session('mensaje') }}</div>
    @endif
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-zinc-900 p-5"><p class="text-sm text-zinc-400">Ingreso original</p><p class="mt-2 text-2xl font-bold">S/ {{ number_format((float)$pago->monto,2) }}</p></div>
        <div class="rounded-xl bg-zinc-900 p-5"><p class="text-sm text-zinc-400">Mes de ingreso</p><p class="mt-2 text-xl font-bold">{{ \Carbon\Carbon::parse($pago->fecha_pago)->locale('es')->translatedFormat('F Y') }}</p></div>
        <div class="rounded-xl bg-zinc-900 p-5"><p class="text-sm text-zinc-400">Pendiente de asignar</p><p class="mt-2 text-2xl font-bold text-amber-400">S/ {{ number_format($sinAsignar,2) }}</p></div>
    </div>
    <div class="rounded-xl border border-blue-500/30 bg-blue-950/20 p-4 text-sm text-blue-100">La fecha del ingreso nunca cambia. El reparto solo indica qué meses se cubrieron con ese dinero; no crea un segundo ingreso ni modifica los pagos ya registrados.</div>

    <section class="rounded-2xl bg-zinc-900 p-6">
        <h2 class="text-lg font-bold">Distribución registrada</h2>
        <div class="mt-4 space-y-2">
            @forelse ($anteriores as $fila)
                <div class="flex items-center justify-between rounded-lg bg-zinc-950 px-4 py-3 text-sm"><span>{{ \Carbon\Carbon::parse($fila->periodo)->locale('es')->translatedFormat('F Y') }} — mes histórico</span><strong>S/ {{ number_format((float)$fila->monto,2) }}</strong></div>
            @empty
                <p class="text-sm text-zinc-400">No hay reparto histórico para este ingreso.</p>
            @endforelse
            @foreach ($cargos as $fila)
                <div class="flex items-center justify-between rounded-lg bg-zinc-950 px-4 py-3 text-sm"><span>{{ $fila->descripcion }} — factura</span><strong>S/ {{ number_format((float)$fila->monto,2) }}</strong></div>
            @endforeach
            @foreach ($exoneraciones as $fila)
                <div class="rounded-lg border border-amber-500/30 p-4 text-sm text-amber-200">{{ \Carbon\Carbon::parse($fila->periodo)->locale('es')->translatedFormat('F Y') }}: EXONERADO. {{ $fila->motivo }}</div>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-emerald-500/30 bg-zinc-900 p-6">
        <h2 class="text-lg font-bold">Conciliar el reparto con una deuda verificada</h2>
        <p class="mt-2 text-sm text-zinc-300">Una asignación de mes por sí sola <strong>NO descuenta</strong> un cargo. Si el pago corresponde a una deuda anterior confirmada, aplica el reparto a ese cargo. Se conserva el ingreso original.</p>
        @error('conciliacion') <p class="mt-3 rounded-lg border border-red-500/40 p-3 text-sm text-red-300">{{ $message }}</p> @enderror
        <div class="mt-4 space-y-3">
            @forelse ($anteriores as $fila)
                <div class="rounded-xl border border-zinc-700 bg-zinc-950 p-4">
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($fila->periodo)->locale('es')->translatedFormat('F Y') }} — S/ {{ number_format((float)$fila->monto, 2) }} <span class="text-xs text-amber-400">(reparto informativo)</span></p>
                    @foreach ($cargosVerificados as $cargo)
                        @if ((float)$cargo->pendiente + 0.0001 >= (float)$fila->monto)
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-800 pt-3 text-sm">
                                <span>Deuda verificada #{{ $cargo->id }} · {{ $cargo->descripcion }} · Vence {{ $cargo->fecha_vencimiento }} · Pendiente S/ {{ number_format($cargo->pendiente,2) }}</span>
                                <button type="button" wire:click="conciliarReparto({{ $fila->id }}, {{ $cargo->id }})"
                                    wire:confirm="¿Confirmas que el pago repartido a {{ $fila->periodo }} corresponde a esta deuda verificada? Se descontará S/ {{ number_format((float)$fila->monto, 2) }} y se conservará el ingreso original."
                                    wire:loading.attr="disabled" wire:target="conciliarReparto"
                                    class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-600 disabled:opacity-60">Aplicar a esta deuda verificada</button>
                            </div>
                        @endif
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-zinc-400">No quedan repartos históricos pendientes de vincular a un cargo.</p>
            @endforelse
        </div>
        @if ($conciliaciones->isNotEmpty())
            <div class="mt-4 rounded-xl bg-emerald-950/30 p-4 text-sm text-emerald-200">
                <p class="font-semibold">Repartos conciliados con deuda (auditoría)</p>
                @foreach ($conciliaciones as $item)
                    <p class="mt-2">{{ \Carbon\Carbon::parse($item->periodo)->locale('es')->translatedFormat('F Y') }}: S/ {{ number_format((float)$item->monto,2) }} aplicados al cargo #{{ $item->cargo_id }}.</p>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-2xl bg-zinc-900 p-6">
        <h2 class="text-lg font-bold">Asignar una parte del ingreso a otro mes</h2>
        <p class="mt-1 text-sm text-zinc-400">Solo si aún queda dinero sin asignar. Para meses con factura descuenta directamente de ella; para meses históricos sin factura guarda la distribución sin inventar cobros.</p>
        @if ($sinAsignar > 0)
            <form wire:submit="guardar" class="mt-5 grid gap-4 sm:grid-cols-3">
                <label class="text-sm">Mes al que corresponde
                    <input wire:model="periodo" required type="month" class="mt-2 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                    @error('periodo') <span class="mt-1 block text-xs text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">Monto a asignar (S/)
                    <input wire:model="monto" required type="number" step="0.01" min="0.01" max="{{ $sinAsignar }}" class="mt-2 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                    @error('monto') <span class="mt-1 block text-xs text-red-400">{{ $message }}</span> @enderror
                </label>
                <div class="self-end"><button wire:loading.attr="disabled" wire:target="guardar" class="w-full rounded-xl bg-blue-700 px-4 py-3 font-semibold text-white">Asignar sin duplicar ingreso</button></div>
            </form>
        @else
            <p class="mt-4 text-sm text-emerald-300">Todo este ingreso ya tiene un destino registrado.</p>
        @endif
    </section>
</div>
