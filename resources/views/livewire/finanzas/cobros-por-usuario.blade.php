<section class="mx-auto mt-6 w-full max-w-7xl space-y-5 pb-10 text-slate-900 dark:text-slate-100">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">Finanzas · Control de cobros</p>
                <h2 class="mt-1 text-2xl font-extrabold">{{ $esAdmin ? 'Cobros por usuario' : 'Mis cobros' }}</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-zinc-400">
                    {{ $esAdmin ? 'Consulta lo que registró cada trabajador, o filtra un usuario.' : 'Aquí solo aparecen los pagos que tú registraste.' }}
                    Cada recibo se cuenta una vez, aunque cubra varios meses.
                </p>
            </div>
            @unless ($esAdmin)
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-zinc-700">← Inicio</a>
            @endunless
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
            <div>
                <label for="cu-anio" class="mb-1 block text-xs font-semibold text-slate-500">Año</label>
                <select id="cu-anio" wire:model.live="anio" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    @foreach ($anios as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cu-mes" class="mb-1 block text-xs font-semibold text-slate-500">Mes</label>
                <select id="cu-mes" wire:model.live="mes" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    @foreach (range(1, 12) as $numero)
                        <option value="{{ sprintf('%02d', $numero) }}">{{ ucfirst(\Carbon\CarbonImmutable::create($anioSeleccionado, $numero, 1, 0, 0, 0, 'America/Lima')->locale('es')->translatedFormat('F')) }}</option>
                    @endforeach
                </select>
            </div>
            @if ($esAdmin)
                <div>
                    <label for="cu-usuario" class="mb-1 block text-xs font-semibold text-slate-500">Usuario</label>
                    <select id="cu-usuario" wire:model.live="usuarioSeleccionado" class="max-w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        <option value="todos">Todos los usuarios</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                        @endforeach
                        <option value="sin_usuario">Sin usuario asignado</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pagos registrados · {{ ucfirst($nombreMes) }}</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">S/ {{ number_format($totalPagos, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cantidad de recibos</p>
            <p class="mt-2 text-3xl font-extrabold">{{ number_format($cantidadPagos) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Importes por medio</p>
            @forelse ($porMedio as $medio)
                <div class="mt-2 flex justify-between gap-2 text-sm"><span>{{ ucfirst(str_replace('_', ' ', $medio->medio)) }} ({{ $medio->cantidad }})</span><strong>S/ {{ number_format((float) $medio->total, 2) }}</strong></div>
            @empty
                <p class="mt-2 text-sm text-slate-500">Sin pagos en este mes.</p>
            @endforelse
        </div>
    </div>

    @if ($esAdmin)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="mb-3 text-lg font-bold">Resumen por trabajador</h3>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-sm">
                    <thead class="border-b border-slate-200 text-left text-xs uppercase text-slate-500 dark:border-zinc-700">
                        <tr><th class="p-3">Registrado por</th><th class="p-3 text-right">Recibos</th><th class="p-3 text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($porUsuario as $fila)
                            <tr class="border-b border-slate-100 dark:border-zinc-800" wire:key="cu-usuario-{{ $fila->usuario_id ?? 'sin' }}">
                                <td class="p-3 font-medium">{{ $fila->name ?: 'Sin usuario asignado' }}</td>
                                <td class="p-3 text-right">{{ $fila->cantidad }}</td>
                                <td class="p-3 text-right font-semibold">S/ {{ number_format((float) $fila->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-4 text-slate-500">No hay pagos para el filtro seleccionado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="mb-2 text-lg font-bold">Detalle de cobros · {{ ucfirst($nombreMes) }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[740px] text-sm">
                <thead class="border-b border-slate-200 text-left text-xs uppercase text-slate-500 dark:border-zinc-700">
                    <tr>
                        <th class="p-3">Cliente</th><th class="p-3">Fecha del pago</th>
                        <th class="p-3">Registrado el</th><th class="p-3">Medio</th>
                        @if ($esAdmin)<th class="p-3">Usuario</th>@endif
                        <th class="p-3 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pagos as $pago)
                        <tr class="border-b border-slate-100 dark:border-zinc-800" wire:key="cu-pago-{{ $pago->id }}">
                            <td class="p-3"><div class="font-semibold">{{ $pago->nombre }}</div><div class="text-xs text-slate-500">{{ $pago->codigo }} · #{{ $pago->id }} · {{ ucfirst($pago->categoria) }}</div></td>
                            <td class="p-3 whitespace-nowrap">{{ \Carbon\CarbonImmutable::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                            <td class="p-3 whitespace-nowrap text-xs">{{ $pago->created_at ? \Carbon\CarbonImmutable::parse($pago->created_at, 'UTC')->setTimezone('America/Lima')->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                            <td class="p-3">{{ ucfirst(str_replace('_', ' ', $pago->medio)) }}</td>
                            @if ($esAdmin)<td class="p-3">{{ $pago->registrado_por ?: 'Sin usuario asignado' }}</td>@endif
                            <td class="p-3 text-right font-bold text-emerald-600 dark:text-emerald-400">S/ {{ number_format((float) $pago->monto, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $esAdmin ? 6 : 5 }}" class="p-4 text-slate-500">No hay cobros registrados en este mes con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $pagos->links() }}</div>
        <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-zinc-400">
            Este es un desglose de <strong>pagos fechados</strong> por la cuenta que los registró, no un ingreso adicional ni un cierre de caja. La fecha del pago puede ser anterior a la fecha en que se registró.
            Los históricos sin recibo ni usuario no se asignan a ningún cobrador, por lo que el total puede diferir del resumen financiero general.
            El efectivo registrado tampoco implica que haya sido entregado al administrador.
        </p>
    </div>
</section>
