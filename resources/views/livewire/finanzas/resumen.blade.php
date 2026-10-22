<div class="mx-auto w-full max-w-7xl space-y-6 pb-12 text-slate-900 dark:text-slate-100">
    <section class="rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-800 px-6 py-8 text-white md:px-9">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold tracking-wide text-blue-100">SISTEMA ISP · FINANZAS</span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight">Ingresos, gastos y resultado</h1>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-blue-100">Un solo resumen mensual de los importes registrados, recuperando los años anteriores. Los cruces sin conciliar se señalan para revisión.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/20">← Panel principal</a>
        </div>
    </section>

    @if (session()->has('finanzas_mensaje'))
        <div class="rounded-xl border border-emerald-700/50 bg-emerald-950/40 px-4 py-3 text-sm font-medium text-emerald-300" role="status">{{ session('finanzas_mensaje') }}</div>
    @endif

    <section class="flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <label for="f-anio" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-zinc-400">Año</label>
            <select id="f-anio" wire:model.live="anio" class="min-w-32 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                @foreach ($anios as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-mes" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-zinc-400">Mes</label>
            <select id="f-mes" wire:model.live="mes" class="min-w-48 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                @foreach ($meses as $fila)
                    <option value="{{ $fila['numero'] }}">{{ $fila['nombre'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="ml-auto">
            <button wire:click="nuevoGasto" type="button" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-500">+ Registrar gasto</button>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-3" aria-label="Resumen del mes">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Ingresos registrados</p>
            <p class="mt-3 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">S/ {{ number_format($ingresosMes, 2) }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400">Historial mensual anterior y pagos fechados; coincidencias del mismo cliente/mes no se suman dos veces</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Gastos realizados</p>
            <p class="mt-3 text-2xl font-extrabold text-rose-600 dark:text-rose-400">S/ {{ number_format($gastosMesTotal, 2) }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400">Gastos activos registrados según fecha</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Resultado mensual orientativo</p>
            <p class="mt-3 text-2xl font-extrabold {{ $resultadoMes >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $resultadoMes < 0 ? '−' : '' }}S/ {{ number_format(abs($resultadoMes), 2) }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400">Ingresos registrados menos gastos; no es un cierre contable ni caja verificada</p>
        </div>

    </section>

    @if ($mostrarFormulario)
        <section class="rounded-2xl border border-blue-500 bg-white p-5 dark:bg-zinc-900" wire:key="form-gasto">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Registrar gasto realizado</h2>
                <button type="button" wire:click="cancelar" class="rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-zinc-600">Cancelar</button>
            </div>
            <form wire:submit="guardarGasto" class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Fecha en que pagaste *</label>
                    <input type="date" wire:model="fechaGasto" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white" />
                    @error('fechaGasto') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Importe (S/) *</label>
                    <input type="number" step="0.01" min="0.01" wire:model="importe" placeholder="Ej. 120.00" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white" />
                    @error('importe') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Categoría *</label>
                    <select wire:model="categoria" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white">
                        @foreach ($categorias as $codigo => $nombre)
                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Medio de pago *</label>
                    <select wire:model="medio" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white">
                        @foreach ($medios as $codigo => $nombre)
                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('medio') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">¿En qué se gastó? *</label>
                    <input wire:model="descripcion" maxlength="255" required placeholder="Ej. Pago de internet mayorista de octubre" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white" />
                    @error('descripcion') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Comprobante / referencia (opcional)</label>
                    <input wire:model="referencia" maxlength="100" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Observación (opcional)</label>
                    <input wire:model="observaciones" maxlength="1000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white" />
                </div>
                <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3">
                    <p class="max-w-xl text-xs text-slate-500 dark:text-zinc-400">Registra aquí dinero que realmente salió. Una factura pendiente de pagar aún no es una salida de caja.</p>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardarGasto" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">Guardar gasto</button>
                </div>
            </form>
        </section>
    @endif

    @if ($gastoAnularId !== null)
        <section class="rounded-2xl border border-rose-600 bg-white p-5 dark:bg-zinc-900" wire:key="form-anulacion-{{ $gastoAnularId }}">
            <h2 class="text-base font-bold">Anular gasto #{{ $gastoAnularId }}</h2>
            <p class="my-2 text-xs text-slate-500 dark:text-zinc-400">No se borra: quedará en MySQL con la fecha y el motivo de anulación. Se excluirá del resumen.</p>
            <form wire:submit="confirmarAnulacion" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium">Motivo obligatorio</label>
                    <input wire:model="motivoAnulacion" placeholder="Ej. Registro repetido" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-950 dark:border-zinc-600 dark:bg-zinc-950 dark:text-white" />
                    @error('motivoAnulacion') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="cancelar" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-zinc-600">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white">Confirmar anulación</button>
                </div>
            </form>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-bold">Resumen por mes · {{ $anioSeleccionado }}</h2>
            <p class="text-xs text-slate-500 dark:text-zinc-400">Un solo ingreso por mes. Consulta otros años en el selector superior.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[660px] text-sm">
                <thead class="border-b border-slate-200 text-left text-xs uppercase text-slate-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr><th class="p-3">Mes</th><th class="p-3 text-right">Ingresos registrados</th><th class="p-3 text-right">Gastos</th><th class="p-3 text-right">Resultado orientativo</th></tr>
                </thead>
                <tbody>
                    @foreach ($meses as $fila)
                        <tr class="border-b border-slate-100 {{ $fila['numero'] === sprintf('%02d', $mesSeleccionado) ? 'bg-blue-50 dark:bg-blue-950/30' : '' }} dark:border-zinc-800" wire:key="mes-finanzas-{{ $fila['numero'] }}">
                            <td class="p-3"><button wire:click="$set('mes', '{{ $fila['numero'] }}')" type="button" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">{{ $fila['nombre'] }}</button></td>
                            <td class="p-3 text-right font-semibold tabular-nums">
                                S/ {{ number_format($fila['ingresos'], 2) }}
                                @if ($fila['pendientes'] > 0)
                                    <span class="block text-xs font-normal text-amber-600 dark:text-amber-400">{{ $fila['pendientes'] }} coincidencia(s) por revisar</span>
                                @endif
                            </td>
                            <td class="p-3 text-right tabular-nums">S/ {{ number_format($fila['gastos'], 2) }}</td>
                            <td class="p-3 text-right font-bold tabular-nums {{ $fila['resultado'] < 0 ? 'text-rose-500' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $fila['resultado'] < 0 ? '−' : '' }}S/ {{ number_format(abs($fila['resultado']), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-300 text-sm font-bold dark:border-zinc-600">
                    <tr>
                        <td class="p-3">Total {{ $anioSeleccionado }}</td>
                        <td class="p-3 text-right">S/ {{ number_format($ingresosAnio, 2) }}</td>
                        <td class="p-3 text-right">S/ {{ number_format($gastosAnio, 2) }}</td>
                        <td class="p-3 text-right {{ ($ingresosAnio - $gastosAnio) < 0 ? 'text-rose-500' : 'text-emerald-500' }}">{{ ($ingresosAnio-$gastosAnio) < 0 ? '−' : '' }}S/ {{ number_format(abs($ingresosAnio-$gastosAnio), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-zinc-400">Los importes antiguos corresponden al mes anotado, no necesariamente al día exacto del cobro. Los pagos nuevos se cuentan en el mes en que se recibieron, aunque paguen servicios de meses futuros. Si el administrador confirmó que una mensualidad anterior y un adelanto son cobros distintos, ambos se suman una sola vez. Los cruces no verificados siguen señalados; el resumen es operativo, no un cierre de caja auditado.</p>
        @if ($pendientesAnio > 0)
            <p class="mt-2 text-sm font-semibold text-amber-700 dark:text-amber-400">{{ $pendientesAnio }} coincidencia(s) cliente/mes de este año requieren revisión para cerrar los importes exactos.</p>
        @endif
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-1 text-lg font-bold">Detalle de ingresos del mes</h2>
            <p class="mb-4 text-xs text-slate-500 dark:text-zinc-400">Un solo importe consolidado por cliente. La procedencia se conserva para poder cotejar el registro.</p>
            <div class="max-h-[440px] space-y-2 overflow-y-auto">
                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Total del mes · S/ {{ number_format($ingresosMes, 2) }}</p>
                @forelse ($detallesIngreso as $d)
                    <div class="rounded-xl border border-slate-200 p-3 text-sm dark:border-zinc-700" wire:key="fin-ingreso-{{ $d['codigo'] }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-bold">{{ $d['nombre'] }} <span class="text-xs font-normal text-slate-500">{{ $d['codigo'] }}</span></p>
                                <p class="mt-1 text-xs {{ $d['procedencia'] === 'Coincidencia por revisar' ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-zinc-400' }}">{{ $d['procedencia'] }}</p>
                                @if ($d['procedencia'] === 'Coincidencia por revisar')
                                    <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">Anterior S/ {{ number_format($d['anterior'], 2) }} · pago fechado S/ {{ number_format($d['pagoInternet'], 2) }}. Se usa provisionalmente el mayor.</p>
                                @elseif (!empty($d['notaConciliacion']))
                                    <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">{{ $d['notaConciliacion'] }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 font-bold text-emerald-600 dark:text-emerald-400">S/ {{ number_format($d['monto'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-zinc-700">No hay ingresos registrados para este mes.</p>
                @endforelse
            </div>
            <details class="mt-4 rounded-xl border border-slate-200 p-3 dark:border-zinc-700">
                <summary class="cursor-pointer text-xs font-semibold text-blue-700 dark:text-blue-300">Ver comprobantes con fecha registrada (sin duplicar su importe)</summary>
                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto">
                    @forelse ($pagosMes as $p)
                        <div class="flex justify-between gap-3 border-b border-slate-100 py-2 text-xs dark:border-zinc-800" wire:key="fin-recibo-{{ $p->id }}">
                            <div>
                                <p class="font-semibold">{{ $p->nombre }} · {{ $p->codigo }}</p>
                                <p class="text-slate-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') }} · {{ ucfirst($p->medio) }} · {{ ucfirst($p->categoria) }}</p>
                            </div>
                            <span class="font-semibold">S/ {{ number_format((float) $p->monto, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">Sin pagos nuevos fechados en este mes.</p>
                    @endforelse
                </div>
            </details>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-1 text-lg font-bold">Gastos del mes</h2>
            <p class="mb-4 text-xs text-slate-500 dark:text-zinc-400">Puedes anular un gasto con motivo; nunca se elimina físicamente.</p>
            <div class="space-y-2">
                @forelse ($gastosMes as $g)
                    <div class="rounded-xl border border-slate-200 p-3 text-sm dark:border-zinc-700" wire:key="fin-gasto-{{ $g->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold {{ $g->anulado_at ? 'line-through opacity-60' : '' }}">{{ $g->descripcion }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($g->fecha_gasto)->format('d/m/Y') }} · {{ $categorias[$g->categoria] ?? $g->categoria }} · {{ $medios[$g->medio] ?? $g->medio }}</p>
                                @if ($g->anulado_at) <p class="mt-1 text-xs text-rose-500">Anulado: {{ $g->motivo_anulacion }}</p> @endif
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2"><span class="font-bold {{ $g->anulado_at ? 'line-through text-slate-500' : 'text-rose-500' }}">S/ {{ number_format((float)$g->monto, 2) }}</span>
                                @if (!$g->anulado_at)
                                    <button type="button" wire:click="solicitarAnulacion({{ $g->id }})" class="text-xs font-semibold text-rose-500 hover:underline">Anular</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500 dark:border-zinc-700">No has registrado gastos para este mes todavía.</p>
                @endforelse
                <div class="pt-2">{{ $gastosMes->links() }}</div>
            </div>
        </div>
    </section>
</div>
