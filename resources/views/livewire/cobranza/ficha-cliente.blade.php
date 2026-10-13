<div class="mx-auto max-w-7xl space-y-5 pb-12">
    <div class="rounded-3xl bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-7 text-white shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-200">Sistema ISP / Ficha de cobranza</p>
                <h1 class="mt-3 text-3xl font-extrabold">{{ $cliente->nombre }}</h1>
                <p class="mt-2 text-sm text-blue-100">{{ $cliente->codigo }} · {{ $cliente->telefono ?: 'Sin celular registrado' }}</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <a href="{{ route('cobranza.index') }}" class="rounded-xl border border-white/30 px-4 py-3 text-white hover:bg-white/10">← Cobranza</a>
                <a href="{{ url('/clientes/'.$cliente->id) }}" class="rounded-xl bg-white px-4 py-3 text-blue-950 hover:bg-blue-100">Ficha del cliente ↗</a>
            </div>
        </div>
        <p class="mt-5 max-w-3xl text-sm text-blue-100">Administra la mensualidad, consulta las deudas pendientes y registra los pagos de este cliente. El historial importado se conserva para consulta.</p>
    </div>

    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-emerald-500/40 bg-emerald-950/30 p-4 text-sm font-semibold text-emerald-300">{{ session('mensaje') }}</div>
    @endif
    @error('operacion')
        <div class="rounded-xl border border-red-500/40 bg-red-950/30 p-4 text-sm text-red-400">{{ $message }}</div>
    @enderror

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="text-xs text-zinc-400">Internet pendiente</div>
            <div class="mt-2 text-2xl font-black text-white">S/ {{ number_format($resumen['internet'], 2) }}</div>
            <div class="mt-2 text-xs text-zinc-400">Saldo real de internet tras aplicar los pagos</div>
        </div>
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="text-xs text-zinc-400">Deuda confirmada pendiente</div>
            <div class="mt-2 text-2xl font-black text-white">S/ {{ number_format($resumen['saldo'], 2) }}</div>
            <div class="mt-2 text-xs text-zinc-400">Internet S/ {{ number_format($resumen['internet'],2) }} · Equipo S/ {{ number_format($resumen['equipo'],2) }}</div>
        </div>
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="text-xs text-zinc-400">Internet vencido confirmado</div>
            <div class="mt-2 text-2xl font-black {{ $resumen['vencido'] > 0 ? 'text-red-400' : 'text-white' }}">S/ {{ number_format($resumen['vencido'],2) }}</div>
            <div class="mt-2 text-xs text-zinc-400">Solo cargos comprobados. No genera corte.</div>
        </div>
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
            <div class="text-xs text-zinc-400">Pago aún sin distribuir</div>
            <div class="mt-2 text-2xl font-black text-emerald-400">S/ {{ number_format($resumen['credito'],2) }}</div>
            <div class="mt-2 text-xs text-zinc-400">Ingresos pendientes de asignar a un mes, no necesariamente anticipos</div>
        </div>
    </div>

    @if (!$ref || $ref->revisado_at === null || $resumen['saldo'] === 0)
        <div class="rounded-xl border border-amber-600/30 bg-amber-950/20 px-5 py-4 text-sm text-amber-100">
            <strong>Importante:</strong> un saldo confirmado en S/ 0.00 no significa necesariamente que el cliente esté al día.
            Las mensualidades que todavia no fueron generadas no están incluidas en los saldos visibles.
        </div>
    @endif

    <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-extrabold">Trabajar con {{ $cliente->nombre }}</h2>
                <p class="mt-1 text-sm text-zinc-400">Selecciona una acción. Solo se guardan cambios cuando pulses Confirmar o Guardar.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="abrir('tarifa')" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-600">Editar mensualidad</button>
                <button type="button" wire:click="abrir('pago')" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-600">Registrar pago nuevo</button>\n                <a href="{{ route('cobranza.adelantos', ['cliente' => $cliente->id]) }}" class="rounded-xl border border-blue-500 px-4 py-2.5 text-sm font-bold text-blue-200 hover:bg-blue-950">Pago de varios meses / adelantado</a>
            </div>
        </div>

        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl bg-zinc-950/70 p-4">
                <p class="text-xs text-zinc-400">Tarifa configurada</p>
                <p class="mt-1 text-lg font-bold">{{ $config && $config->tarifa_mensual !== null ? 'S/ '.number_format((float)$config->tarifa_mensual, 2) : 'Sin configurar' }}</p>
            </div>
            <div class="rounded-xl bg-zinc-950/70 p-4">
                <p class="text-xs text-zinc-400">Inicio del cobro</p>
                <p class="mt-1 text-lg font-bold">{{ $config?->inicio_cobro ?: 'Pendiente' }}</p>
            </div>
            <div class="rounded-xl bg-zinc-950/70 p-4">
                <p class="text-xs text-zinc-400">Facturación automática</p>
                <p class="mt-1 text-lg font-bold {{ $config?->habilitado ? 'text-emerald-400' : 'text-amber-400' }}">{{ $config?->habilitado ? 'Habilitada' : 'Deshabilitada' }}</p>
            </div>
        </div>

        @if ($formulario !== '')
            <div class="mt-5 rounded-2xl border border-blue-600/50 bg-zinc-950/70 p-5" id="formulario-cliente">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h3 class="text-lg font-bold">
                        @if ($formulario === 'tarifa') Mensualidad de este cliente @else Registrar dinero recibido @endif
                    </h3>
                    <button type="button" wire:click="cerrar" class="rounded-lg border border-zinc-600 px-3 py-2 text-sm hover:bg-zinc-800">Cancelar</button>
                </div>

                @if ($formulario === 'tarifa')
                    <p class="mb-4 text-sm text-amber-200">Verifica el precio y si sigue recibiendo internet. Habilitar facturación permite crear cargos mensuales. Los cargos ya emitidos conservan su importe original.</p>
                    <form wire:submit="guardarTarifa" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm">Mensualidad (S/)
                                <input type="number" min="0.01" step="0.01" required wire:model="tarifa" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                @error('tarifa') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm">Inicio del cobro
                                <input type="date" required wire:model="inicioCobro" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                @error('inicioCobro') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <label class="flex items-start gap-3 rounded-xl border border-zinc-700 p-4 text-sm">
                            <input type="checkbox" wire:model="habilitado" class="mt-1">
                            <span><strong>Habilitar facturación automática</strong><br><span class="text-zinc-400">Solo si este cliente debe recibir mensualidades nuevas. No cambia su conexión.</span></span>
                        </label>
                        <button type="submit" wire:loading.attr="disabled" wire:target="guardarTarifa" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">Guardar mensualidad</button>
                    </form>
                @elseif ($formulario === 'pago')
                    <p class="mb-4 text-sm text-amber-200">Registra únicamente pagos nuevos comprobados. Las casillas históricas del Excel ya fueron importadas y no deben registrarse otra vez.</p>
                    <form wire:submit="guardarPago" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="block text-sm">Importe recibido (S/)
                                <input type="number" min="0.01" step="0.01" required wire:model="importe" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                @error('importe') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm">Fecha real del pago
                                <input type="date" max="{{ today('America/Lima')->toDateString() }}" required wire:model.live="fechaPago" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                @error('fechaPago') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm">Medio
                                <select wire:model="medio" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                    <option value="efectivo">Efectivo</option><option value="yape">Yape</option><option value="plin">Plin</option><option value="transferencia">Transferencia</option><option value="otro">Otro</option>
                                </select>
                                @error('medio') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <label class="block text-sm">Aplicar a
                            <select wire:model.live="categoria" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                <option value="internet">Internet</option><option value="equipo">Equipo / router</option><option value="otros">Otros</option>
                            </select>
                            @error('categoria') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @if ($categoria === 'internet')
                                <label class="block text-sm">Mes del servicio de internet
                                    <input type="month" required wire:model.live="mesPago" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                    @error('mesPago') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                                </label>
                            @else
                                <div class="rounded-xl border border-zinc-700 p-3 text-xs text-zinc-300">Para equipo/router selecciona la deuda del equipo. El mes de ingreso se obtiene de la fecha real del pago.</div>
                            @endif
                            <label class="block text-sm">Deuda a cancelar / destino del dinero
                                <select required wire:key="destino-{{ $categoria }}-{{ $mesPago }}" wire:model="destinoPago" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                    <option value="">Selecciona a qué se aplica</option>
                                    @foreach ($opcionesPago as $opcion)
                                        <option value="{{ $opcion['valor'] }}">{{ $opcion['texto'] }}</option>
                                    @endforeach
                                </select>
                                @error('destinoPago') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        @if ($categoria === 'equipo' && count($opcionesPago) === 0)
                            <p class="rounded-lg border border-amber-500/40 bg-amber-950/30 p-3 text-xs text-amber-200">No hay deuda de equipo registrada. Antes de recibir un nuevo pago del router, crea/verifica el cargo de equipo en Cobranza. Si el dinero YA fue ingresado, no lo registres otra vez.</p>
                        @endif
                        <p class="text-xs text-blue-200">La fecha determina el mes de INGRESO en Finanzas. La deuda y el mes seleccionados indican qué queda pagado; son datos diferentes.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm">Referencia / número de operación (opcional)
                                <input type="text" maxlength="100" wire:model="referenciaPago" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                @error('referenciaPago') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-sm">Observaciones (opcional)
                                <input type="text" maxlength="1000" wire:model="observaciones" class="mt-1 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white">
                                @error('observaciones') <span class="mt-1 block text-red-400">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <p class="text-xs text-zinc-400">En un solo clic se registra el ingreso y se aplica al destino seleccionado. Para dividir un pago entre varias deudas o meses, registra el ingreso con el procedimiento de reparto existente; no lo dupliques.</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="guardarPago" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">Registrar pago confirmado</button>
                    </form>
                @endif
            </div>
        @endif
    </section>

    <div class="grid gap-5 md:grid-cols-2">
        {{-- MISMA UBICACIÓN Y DISEÑO: HISTORIAL UNIFICADO SOLO DE LECTURA --}}
        @php
            $todosRepartos = $repartos->collapse();
            $mesesDelHistorial = $historial->pluck('periodo')
                ->merge($pagos->pluck('fecha_pago'))
                ->merge($todosRepartos->pluck('periodo'))
                ->merge($exoneraciones->pluck('periodo'))
                ->merge($imputacionesDirectas->collapse()->pluck('periodo'))
                ->merge($anticipos->pluck('periodo'))
                ->map(fn ($fecha) => substr((string) $fecha, 0, 7))
                ->filter()
                ->unique()
                ->sortDesc()
                ->values();
        @endphp
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold">Historial de pagos</h2>
                    <p class="mt-1 text-sm text-zinc-400">Todos los meses: anotaciones recuperadas del Excel y pagos nuevos registrados en MySQL.</p>
                </div>
                <span class="rounded-lg bg-zinc-800 px-3 py-2 text-xs text-zinc-300">{{ $mesesDelHistorial->count() }} meses</span>
            </div>
            <div class="mt-3 rounded-xl border border-blue-500/30 bg-blue-950/20 px-3 py-3 text-xs text-blue-100">
                <span class="font-bold text-amber-300">Excel:</span> importe anotado en el archivo antiguo, sin fecha exacta.
                <span class="font-bold text-emerald-300">Pago nuevo:</span> dinero ingresado realmente, según su fecha de cobro.
                <span class="font-bold text-sky-300">Asignado:</span> mes de servicio al que corresponde; no es otro ingreso.
            </div>
            
            <div class="mt-4 max-h-[430px] space-y-2 overflow-y-auto pr-1">
                @forelse ($mesesDelHistorial as $mes)
                    @php
                        $registrosExcel = $historial->filter(fn ($h) => substr((string) $h->periodo, 0, 7) === $mes);
                        $ingresosMes = $pagos->filter(fn ($p) => substr((string) $p->fecha_pago, 0, 7) === $mes);
                        $asignacionesMes = $todosRepartos->filter(fn ($r) => substr((string) $r->periodo, 0, 7) === $mes);
                        $exoneracionesMes = $exoneraciones->filter(fn ($e) => substr((string) $e->periodo, 0, 7) === $mes);
                        $pagosAplicadosAlMes = $imputacionesDirectas->collapse()->filter(fn ($a) => substr((string)$a->periodo, 0, 7) === $mes);
                        $anticiposDelMes = $anticipos->filter(fn ($a) => substr((string)$a->periodo, 0, 7) === $mes);
                    @endphp
                    <div wire:key="mes-historial-{{ $mes }}" class="rounded-xl border border-zinc-800 bg-zinc-950/70 px-4 py-3 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong class="capitalize">{{ \Carbon\Carbon::parse($mes.'-01')->locale('es')->translatedFormat('F Y') }}</strong>
                            @if ($ingresosMes->isNotEmpty())
                                <span class="font-bold text-emerald-300">Ingresó S/ {{ number_format((float) $ingresosMes->sum('monto'), 2) }}</span>
                            @endif
                        </div>
                        @foreach ($registrosExcel as $h)
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-zinc-800 pt-2 text-xs">
                                <span class="text-amber-300">Excel antiguo · Celda {{ $h->celda_excel }}</span>
                                <span class="font-semibold">S/ {{ number_format((float) $h->importe_anotado, 2) }}</span>
                            </div>
                        @endforeach
                        @foreach ($ingresosMes as $p)
                            <div class="mt-2 border-t border-zinc-800 pt-2 text-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-emerald-300">Pago registrado · {{ \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') }}</span>
                                    <span class="font-bold text-emerald-300">S/ {{ number_format((float) $p->monto, 2) }}</span>
                                </div>
                                <p class="mt-1 text-zinc-400">{{ strtoupper($p->medio) }} · {{ ucfirst($p->categoria) }}</p>
                                @if ($p->observaciones)
                                    <p class="mt-1 text-zinc-400">{{ $p->observaciones }}</p>
                                @endif
                            </div>
                        @endforeach
                        @foreach ($asignacionesMes as $a)
                            <div class="mt-2 border-t border-zinc-800 pt-2 text-xs text-sky-300">
                                Pago #{{ $a->pago_id }} asignado a este mes: S/ {{ number_format((float) $a->monto, 2) }}
                                <span class="text-zinc-500">(no es un ingreso adicional)</span>
                            </div>
                        @endforeach
                        @foreach ($pagosAplicadosAlMes as $a)
                            <div class="mt-2 border-t border-zinc-800 pt-2 text-xs text-sky-300">Pago #{{ $a->pago_id }} aplicado a deuda verificada: S/ {{ number_format((float)$a->monto, 2) }} <span class="text-zinc-500">(no es otro ingreso)</span></div>
                        @endforeach
                        @foreach ($anticiposDelMes as $adelanto)
                            <div class="mt-2 border-t border-zinc-800 pt-2 text-xs text-sky-300">
                                Pago #{{ $adelanto->pago_id }} reservado para este mes: S/ {{ number_format((float)$adelanto->monto, 2) }}.
                                <span class="text-zinc-400">{{ (float)$adelanto->monto_aplicado > 0 ? 'Aplicado al cargo: S/ '.number_format((float)$adelanto->monto_aplicado,2) : 'Esperando mensualidad (no es otro ingreso)' }}</span>
                                @if ((float)$adelanto->monto_aplicado > 0 && (float)$adelanto->monto_aplicado < (float)$adelanto->monto)
                                    <span class="text-amber-300">Queda reserva sin aplicar: S/ {{ number_format((float)$adelanto->monto - (float)$adelanto->monto_aplicado, 2) }}; revisar cambio de tarifa.</span>
                                @endif
                            </div>
                        @endforeach
                        @foreach ($exoneracionesMes as $e)
                            <p class="mt-2 border-t border-zinc-800 pt-2 text-xs text-amber-300">Mes exonerado: {{ $e->motivo }}</p>
                        @endforeach
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-zinc-500">Este cliente aún no tiene historial registrado.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 sm:p-6">
            <h2 class="text-lg font-extrabold">Cargos y deuda actual</h2>
            <p class="mt-1 text-sm text-zinc-400">Solo cargos guardados en el sistema, con importe original y saldo pendiente.</p>
            <div class="mt-4 max-h-[490px] space-y-2 overflow-y-auto pr-1">
                
                @php
                    $cargosPendientes = array_filter(
                        $cargosConSaldo,
                        fn ($item) => (float) $item['pendiente'] > 0
                    );
                @endphp

                @forelse ($cargosPendientes as $item)

                    @php $cargo = $item['cargo']; @endphp
                    <div wire:key="cargo-{{ $cargo->id }}" class="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4 text-sm">
                        <div class="flex flex-wrap justify-between gap-2">
                            <strong>{{ $cargo->tipo === 'saldo_excel_verificado' ? 'Deuda anterior confirmada' : $cargo->descripcion }}</strong>
                            <span class="font-bold">S/ {{ number_format((float)$cargo->monto,2) }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-zinc-400">
                            <span>{{ ucfirst($cargo->categoria) }} · Vence {{ $cargo->fecha_vencimiento }}</span>
                            <span class="font-bold {{ $item['pendiente'] > 0 ? 'text-amber-400' : 'text-emerald-400' }}">Pendiente S/ {{ number_format($item['pendiente'],2) }}</span>
                        </div>
                        @if ($item['pendiente'] > 0 && in_array($cargo->tipo, ['cargo_manual', 'mensualidad_automatica'], true) && $cargo->categoria === 'internet')
                            <button type="button" wire:click="abrirCorreccionCargo({{ $cargo->id }})"
                                class="mt-3 rounded-lg border border-blue-500/50 px-3 py-2 text-xs font-semibold text-blue-300 hover:bg-blue-950/40">
                                Corregir importe y motivo
                            </button>
                        @endif
                        @foreach ($historialCorrecciones->get($cargo->id, collect()) as $ajuste)
                            <div class="mt-2 border-t border-zinc-800 pt-2 text-xs text-zinc-400">
                                Corrección {{ $ajuste->created_at }}: S/ {{ number_format((float)$ajuste->monto_anterior, 2) }} →
                                S/ {{ number_format((float)$ajuste->monto_nuevo, 2) }}.
                                <span class="text-zinc-300">Motivo: {{ $ajuste->motivo }}</span>
                            </div>
                        @endforeach
                        @if ($cargoCorreccionId === (int) $cargo->id)
                            <form wire:submit="guardarCorreccionCargoAuditada" class="mt-4 space-y-3 rounded-xl border border-blue-600/50 bg-zinc-900 p-3">
                                <p class="text-xs font-semibold text-blue-200">Corregir este cargo #{{ $cargo->id }} (sin registrar un pago)</p>
                                <p class="text-xs text-zinc-400">Importe actual: S/ {{ number_format((float)$cargo->monto, 2) }}. El cambio ajustará el saldo pendiente.</p>
                                <label class="block text-xs font-semibold">Importe correcto (S/)
                                    <input wire:model="cargoCorreccionMonto" type="number" min="0.01" step="0.01" required
                                        class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white">
                                </label>
                                @error('cargoCorreccionMonto') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                                <label class="block text-xs font-semibold">Motivo obligatorio
                                    <textarea wire:model="cargoCorreccionMotivo" required minlength="10" maxlength="500" rows="2"
                                        placeholder="Ej.: tarifa de octubre registrada por error a S/ 70; la mensualidad acordada era S/ 60."
                                        class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-white"></textarea>
                                </label>
                                @error('cargoCorreccionMotivo') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                                @error('correccionCargo') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white">Guardar corrección</button>
                                    <button type="button" wire:click="cerrarCorreccionCargo" class="rounded-lg border border-zinc-700 px-3 py-2 text-xs">Cancelar</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-zinc-700 p-5 text-sm text-zinc-400">No hay cargos pendientes registrados. Revisa si existen mensualidades por generar o saldos antiguos por verificar.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">Pagos registrados en el sistema</h2>
                <p class="mt-1 text-sm text-zinc-400">Aquí aparecen los ingresos recibidos; su fecha determina la caja mensual, y puedes indicar los meses cubiertos con cada uno.</p>
            </div>
            <button wire:click="abrir('pago')" type="button" class="rounded-xl border border-emerald-500/50 px-4 py-2 text-sm font-semibold text-emerald-400 hover:bg-emerald-950/30">+ Registrar pago</button>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($pagos as $p)
                <div wire:key="pago-{{ $p->id }}" class="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <strong class="text-lg text-emerald-400">S/ {{ number_format((float)$p->monto, 2) }}</strong>
                        <span class="text-xs text-zinc-400">{{ $p->fecha_pago }}</span>
                    </div>
                    <div class="mt-2 text-xs text-zinc-400">{{ strtoupper($p->medio) }} · {{ ucfirst($p->categoria) }}</div>
                    @if ($p->referencia) <div class="mt-1 text-xs text-zinc-400">Ref: {{ $p->referencia }}</div> @endif
                    @if ($p->observaciones) <p class="mt-2 text-xs text-zinc-400">{{ $p->observaciones }}</p> @endif
                    <a href="{{ route('cobranza.distribuir-pago', $p->id) }}" class="mt-3 inline-block rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white">Ver / distribuir por meses</a>
                    @foreach ($repartos->get($p->id, collect()) as $r)
                        <p class="mt-1 text-xs text-emerald-300">{{ \Carbon\Carbon::parse($r->periodo)->locale('es')->translatedFormat('F Y') }}: S/ {{ number_format((float)$r->monto, 2) }}</p>
                    @endforeach
                    @foreach ($imputacionesDirectas->get($p->id, collect()) as $r)
                        <p class="mt-1 text-xs text-emerald-300">{{ \Carbon\Carbon::parse($r->periodo)->locale('es')->translatedFormat('F Y') }}: S/ {{ number_format((float)$r->monto, 2) }} (pagado sobre deuda verificada)</p>
                    @endforeach
                    @foreach ($conciliaciones->get($p->id, collect()) as $r)
                        <p class="mt-1 text-xs text-emerald-300">{{ \Carbon\Carbon::parse($r->periodo)->locale('es')->translatedFormat('F Y') }}: S/ {{ number_format((float)$r->monto, 2) }} (aplicado a deuda verificada)</p>
                    @endforeach
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-700 p-5 text-sm text-zinc-400">Aún no se ha registrado ningún pago nuevo para este cliente.</div>
            @endforelse
        </div>
    </section>

    <div class="text-xs text-zinc-500">Pantalla administrativa local. Los estados de corte son informativos; esta ficha no suspende servicios.</div>
</div>
