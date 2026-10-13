<div class="mx-auto max-w-4xl space-y-5 pb-12">
    <div class="rounded-3xl bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-6 text-white">
        <a href="{{ route('cobranza.ficha', ['cliente' => $clienteId]) }}" class="text-sm font-semibold text-blue-100 hover:text-white">← Volver a la ficha</a>
        <h1 class="mt-4 text-2xl font-extrabold">Pago de varios meses</h1>
        <p class="mt-2 text-sm text-blue-100">{{ $cliente->codigo }} · {{ $cliente->nombre }}</p>
        <p class="mt-2 text-sm text-blue-100">Un único ingreso en Finanzas. Tú eliges cuánto corresponde a cada mes.</p>
    </div>

    <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 text-white sm:p-6">
        @if (!$config || !$config->habilitado || !$config->tarifa_mensual)
            <p class="mb-4 rounded-xl border border-amber-500/40 p-3 text-sm text-amber-200">Para reservar meses futuros necesitas configurar y habilitar la mensualidad en la ficha del cliente.</p>
        @else
            <p class="mb-4 text-sm text-zinc-300">Tarifa configurada de referencia: <strong>S/ {{ number_format((float) $config->tarifa_mensual, 2) }}</strong>. Los cargos ya generados pueden tener otro importe o saldo.</p>
        @endif
        <form wire:submit="guardar" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block text-sm">Total realmente recibido (S/)
                    <input type="number" wire:model.live.debounce.400ms="importe" min="0.01" step="0.01" required class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                    @error('importe') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">Fecha real del cobro
                    <input type="date" wire:model="fechaPago" max="{{ today('America/Lima')->toDateString() }}" required class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                    @error('fechaPago') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">Medio de pago
                    <select wire:model="medio" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                        <option value="efectivo">Efectivo</option><option value="yape">Yape</option><option value="plin">Plin</option><option value="transferencia">Transferencia</option><option value="otro">Otro</option>
                    </select>
                    @error('medio') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="rounded-xl border border-blue-500/40 bg-blue-950/20 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="font-bold">Distribución del pago</h2><p class="mt-1 text-xs text-zinc-300">Elige cada mes y escribe su importe. Puedes dejar un mes parcialmente pagado.</p></div>
                    <button type="button" wire:click="agregarMes" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white">+ Agregar mes</button>
                </div>
                @error('meses') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                <div class="mt-4 space-y-3">
                    @foreach ($meses as $i => $fila)
                        <div wire:key="adelanto-{{ $i }}" class="flex flex-wrap items-end gap-3 rounded-xl bg-zinc-950/80 p-3">
                            <label class="min-w-[145px] flex-1 text-sm">Mes del servicio
                                <input type="month" wire:model.live="meses.{{ $i }}.periodo" required class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-white">
                                @error('meses.'.$i.'.periodo') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="min-w-[140px] flex-1 text-sm">Importe para ese mes (S/)
                                <input type="number" min="0.01" step="0.01" wire:model.live.debounce.400ms="meses.{{ $i }}.monto" required class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-white">
                                @error('meses.'.$i.'.monto') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                            @if (count($meses) > 1)
                                <button type="button" wire:click="quitarMes({{ $i }})" class="rounded-lg border border-red-500/50 px-3 py-2.5 text-sm text-red-300">Quitar</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-zinc-950 p-3 text-sm">Recibido<br><strong>S/ {{ number_format((float) $importe, 2) }}</strong></div>
                    <div class="rounded-lg bg-zinc-950 p-3 text-sm">Distribuido<br><strong>S/ {{ $sumaAsignada }}</strong></div>
                    <div class="rounded-lg bg-zinc-950 p-3 text-sm">Diferencia<br><strong class="{{ (float) $diferencia === 0.0 ? 'text-emerald-400' : 'text-amber-300' }}">S/ {{ $diferencia }}</strong></div>
                </div>
                <p class="mt-3 text-xs text-zinc-300">El total distribuido debe ser igual al recibido. Una mensualidad futura queda RESERVADA hasta que se genere su cargo; entonces el sistema aplica ese dinero sin crear otro ingreso.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">Número de operación (opcional)
                    <input type="text" wire:model="referenciaPago" maxlength="100" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                    @error('referenciaPago') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">Observaciones (opcional)
                    <input type="text" wire:model="observaciones" maxlength="1000" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                    @error('observaciones') <span class="text-red-400">{{ $message }}</span> @enderror
                </label>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="guardar" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">Guardar UN pago y distribuir</button>
                <a href="{{ route('cobranza.ficha', ['cliente' => $clienteId]) }}" class="rounded-xl border border-zinc-600 px-5 py-3 text-sm font-semibold text-white">Cancelar</a>
            </div>
        </form>
    </section>
</div>
