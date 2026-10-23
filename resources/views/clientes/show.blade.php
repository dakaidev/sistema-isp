<x-layouts::app :title="'Ficha del Cliente | Sistema ISP'">
    @php
        // Presentacion solamente. Todos los importes provienen del resumen de consulta existente.
        $facturacion = $resumenFicha['configuracion'];
        $saldos = $resumenFicha['saldos'];
        $mesesPendientes = $resumenFicha['meses_pendientes'];
        $ultimoPago = $resumenFicha['ultimo_pago'];
        $serviciosActivos = $cliente->servicios->where('estado', 'activo');
        $totalServicios = $cliente->servicios->count();
        $suspendidos = $cliente->servicios->where('estado', 'suspendido')->count();
        $estadoConexion = $totalServicios === 0 ? 'Sin conexión registrada'
            : ($serviciosActivos->isNotEmpty() && $suspendidos > 0 ? 'Estado mixto'
            : ($serviciosActivos->isNotEmpty() ? 'Activo'
            : ($suspendidos > 0 ? 'Suspendido' : 'Sin estado definido')));
        $estadoFacturacion = !$facturacion ? 'Sin configurar' : ($facturacion->habilitado ? 'Habilitada' : 'Deshabilitada');
        $servicioPrincipal = $serviciosActivos->first() ?? $cliente->servicios->first();
        $requiereRevision = ($serviciosActivos->isEmpty() && $suspendidos > 0 && $facturacion?->habilitado)
            || ($serviciosActivos->isNotEmpty() && $facturacion && !$facturacion->habilitado);
        $ubicacion = $cliente->sede === 'upina' ? 'Upina' : ($cliente->sede === 'calasuca' ? 'Calasuca' : ($cliente->sede ?: 'Sin sede'));
    @endphp

    <style>[x-cloak]{display:none!important}</style>

    <div class="mx-auto max-w-6xl space-y-5 pb-12" x-data="{ tab: 'resumen' }">
        {{-- Navegacion compacta --}}
        <nav aria-label="Ruta de navegacion" class="flex flex-wrap items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('clientes.index') }}" wire:navigate class="font-medium hover:text-blue-600 dark:hover:text-blue-400">Clientes</a>
            <span aria-hidden="true">/</span>
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $cliente->codigo }} — Ficha</span>
        </nav>

        {{-- Resumen en un solo panel: sin datos de ejemplo --}}
        <header class="overflow-hidden rounded-3xl border border-blue-700/20 bg-gradient-to-br from-slate-950 via-blue-950 to-blue-800 text-white shadow-lg shadow-blue-950/10">
            <div class="p-5 sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-blue-400/15 px-3 py-1 text-xs font-bold tracking-wide text-blue-100">{{ $cliente->codigo }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $estadoConexion === 'Activo' ? 'bg-emerald-400/20 text-emerald-200' : ($estadoConexion === 'Suspendido' ? 'bg-rose-400/20 text-rose-200' : 'bg-amber-300/20 text-amber-100') }}">
                                {{ $estadoConexion }}
                            </span>
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $cliente->nombre }}</h1>
                        <p class="mt-1 text-sm text-blue-100">{{ $ubicacion }} · {{ $servicioPrincipal?->nombre_plan ?: 'Plan sin registrar' }} · {{ $cliente->telefono ?: 'Sin teléfono' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('clientes.index', ['cliente' => $cliente->id]) }}" class="rounded-xl border border-white/35 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Editar datos</a>
                        <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-blue-950 hover:bg-blue-50">Ir a Cobranza →</a>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 border-t border-white/20 pt-5 sm:grid-cols-3 sm:gap-4">
                    <div class="rounded-xl bg-white/10 px-4 py-3">
                        <p class="text-xs font-medium text-blue-100">Tarifa mensual oficial</p>
                        <p class="mt-1 text-2xl font-extrabold">{{ $facturacion && $facturacion->tarifa_mensual !== null ? 'S/ '.number_format((float) $facturacion->tarifa_mensual, 2) : 'Sin configurar' }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 px-4 py-3">
                        <p class="text-xs font-medium text-blue-100">Velocidad registrada</p>
                        <p class="mt-1 text-2xl font-extrabold">{{ $servicioPrincipal?->velocidad_mbps ? $servicioPrincipal->velocidad_mbps.' Mbps' : 'Sin registrar' }}</p>
                    </div>
                    <div class="rounded-xl bg-white/10 px-4 py-3">
                        <p class="text-xs font-medium text-blue-100">Deuda confirmada</p>
                        <p class="mt-1 text-2xl font-extrabold {{ $saldos['total'] > 0 ? 'text-rose-200' : 'text-emerald-200' }}">S/ {{ number_format($saldos['total'], 2) }}</p>
                    </div>
                </div>
            </div>
        </header>

        @if ($requiereRevision)
            <div role="status" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-400/40 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                <p><strong>Revisar estados:</strong> la situación técnica y la facturación no coinciden. Es solo una alerta; no realiza cortes ni genera cargos.</p>
                <a href="{{ route('clientes.index', ['cliente' => $cliente->id]) }}" class="font-bold underline">Revisar →</a>
            </div>
        @endif

        {{-- Pestañas para no obligar al operador a recorrer toda la ficha --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="overflow-x-auto border-b border-zinc-200 px-3 pt-3 dark:border-zinc-800 sm:px-5">
                <div role="tablist" aria-label="Secciones de la ficha" class="flex min-w-max gap-1">
                    @foreach ([
                        'resumen' => 'Resumen',
                        'servicio' => 'Servicio',
                        'pagos' => 'Pagos y deuda',
                        'datos' => 'Datos y ubicación',
                    ] as $clave => $nombre)
                        <button type="button" role="tab" x-on:click="tab = '{{ $clave }}'"
                            x-bind:aria-selected="tab === '{{ $clave }}' ? 'true' : 'false'"
                            x-bind:class="tab === '{{ $clave }}' ? 'bg-blue-600 text-white shadow-sm' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors">
                            {{ $nombre }}
                            @if ($clave === 'pagos' && count($mesesPendientes) > 0)
                                <span class="ml-1 rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ count($mesesPendientes) }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- RESUMEN --}}
            <div x-show="tab === 'resumen'" role="tabpanel" class="space-y-5 p-4 sm:p-6">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="text-xs text-zinc-500">Conexión a Internet</p>
                        <p class="mt-2 text-lg font-bold">{{ $estadoConexion }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $serviciosActivos->count() }} activas de {{ $totalServicios }} registradas</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="text-xs text-zinc-500">Facturación mensual</p>
                        <p class="mt-2 text-lg font-bold">{{ $estadoFacturacion }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $facturacion?->inicio_cobro ? 'Inicio: '.\Carbon\Carbon::parse($facturacion->inicio_cobro)->format('d/m/Y') : 'Sin fecha de inicio' }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <p class="text-xs text-zinc-500">Meses emitidos pendientes</p>
                        <p class="mt-2 text-lg font-bold {{ count($mesesPendientes) ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ count($mesesPendientes) }} {{ count($mesesPendientes) === 1 ? 'mes' : 'meses' }}</p>
                        <p class="mt-1 text-xs text-zinc-500">Solo cargos emitidos con saldo</p>
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-800/50">
                        <h2 class="font-bold">Estado de pagos</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-zinc-500">Deuda total confirmada</span><span class="font-bold">S/ {{ number_format($saldos['total'], 2) }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-zinc-500">Internet vencido</span><span class="font-bold">S/ {{ number_format($saldos['vencido_internet'], 2) }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-zinc-500">Último pago</span><span class="font-bold">{{ $ultimoPago ? 'S/ '.number_format((float) $ultimoPago->monto, 2) : 'Sin registrar' }}</span></div>
                        </div>
                        <button type="button" x-on:click="tab = 'pagos'" class="mt-4 text-sm font-bold text-blue-600 hover:underline dark:text-blue-400">Ver detalle de pagos →</button>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-800/50">
                        <h2 class="font-bold">Servicio principal</h2>
                        @if ($servicioPrincipal)
                            <p class="mt-3 text-lg font-bold">{{ $servicioPrincipal->tipo_conexion === 'fibra' ? 'Fibra óptica' : 'Internet inalámbrico' }}</p>
                            <p class="mt-1 text-sm text-zinc-500">{{ $servicioPrincipal->nombre_plan ?: 'Sin nombre de plan' }}</p>
                            <p class="mt-3 text-sm">Velocidad: <strong>{{ $servicioPrincipal->velocidad_mbps ? $servicioPrincipal->velocidad_mbps.' Mbps' : 'Sin registrar' }}</strong></p>
                        @else
                            <p class="mt-3 text-sm text-zinc-500">Este cliente todavía no tiene servicio técnico documentado.</p>
                        @endif
                        <button type="button" x-on:click="tab = 'servicio'" class="mt-4 text-sm font-bold text-blue-600 hover:underline dark:text-blue-400">Ver conexiones →</button>
                    </div>
                </div>
                <p class="text-xs text-zinc-500">La deuda se calcula a partir de cargos y pagos aplicados. Los registros históricos sin cargo confirmado no se suman como deuda nueva.</p>
            </div>

            {{-- SERVICIO --}}
            <div x-show="tab === 'servicio'" x-cloak role="tabpanel" class="space-y-4 p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-lg font-extrabold">Servicios de Internet</h2><p class="text-xs text-zinc-500">Conexiones técnicas contratadas por el cliente.</p></div>
                    <a href="{{ route('servicios.index') }}" wire:navigate class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Gestionar servicios</a>
                </div>
                @forelse ($cliente->servicios as $servicio)
                    <article class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold">{{ $servicio->tipo_conexion === 'fibra' ? 'Fibra óptica' : 'Internet inalámbrico' }}</h3>
                                <p class="text-sm text-zinc-500">{{ $servicio->nombre_plan ?: 'Plan sin registrar' }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $servicio->estado === 'activo' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' }}">{{ ucfirst($servicio->estado ?? 'Sin estado') }}</span>
                        </div>
                        <div class="mt-4 grid gap-4 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-700 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ([
                                'Velocidad' => $servicio->velocidad_mbps ? $servicio->velocidad_mbps.' Mbps' : null,
                                'Tarifa técnica de referencia' => $servicio->tarifa_mensual !== null ? 'S/ '.number_format((float) $servicio->tarifa_mensual, 2) : null,
                                'Fecha de activación' => $servicio->fecha_activacion?->format('d/m/Y'),
                                'IP asignada' => $servicio->ip_asignada,
                                'MAC del equipo' => $servicio->mac_equipo,
                                'Dirección de instalación' => $servicio->direccion_instalacion,
                            ] as $rotulo => $valor)
                                <div><p class="text-xs text-zinc-500">{{ $rotulo }}</p><p class="mt-1 break-words font-semibold">{{ $valor ?: 'Sin registrar' }}</p></div>
                            @endforeach
                        </div>
                        @if ($servicio->observaciones)<p class="mt-4 border-t border-zinc-200 pt-3 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">{{ $servicio->observaciones }}</p>@endif
                    </article>
                @empty
                    <p class="rounded-xl bg-zinc-100 p-5 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">No tiene conexión técnica documentada. Esto no determina por sí solo su estado de facturación.</p>
                @endforelse
            </div>

            {{-- PAGOS Y DEUDA --}}
            <div x-show="tab === 'pagos'" x-cloak role="tabpanel" class="space-y-5 p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-lg font-extrabold">Pagos y deudas</h2><p class="text-xs text-zinc-500">Saldos confirmados. La gestión y el registro de pagos siguen en Cobranza.</p></div>
                    <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Abrir Cobranza →</a>
                </div>
                <div class="grid gap-3 sm:grid-cols-4">
                    @foreach ([
                        'Total pendiente' => $saldos['total'],
                        'Internet' => $saldos['internet'],
                        'Equipos' => $saldos['equipo'],
                        'Otros' => $saldos['otros'],
                    ] as $etiqueta => $monto)
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/60"><p class="text-xs text-zinc-500">{{ $etiqueta }}</p><p class="mt-2 text-xl font-extrabold {{ $monto > 0 ? 'text-rose-600 dark:text-rose-400' : '' }}">S/ {{ number_format($monto, 2) }}</p></div>
                    @endforeach
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <section class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        <h3 class="font-bold">Mensualidades pendientes</h3>
                        <p class="mt-1 text-xs text-zinc-500">Únicamente meses con cargo emitido y saldo restante.</p>
                        @if (count($mesesPendientes))
                            <div class="mt-4 space-y-2">
                                @foreach ($mesesPendientes as $periodo => $importe)
                                    <div class="flex justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-2.5 text-sm dark:bg-zinc-800/60">
                                        <span class="capitalize">{{ \Carbon\Carbon::parse($periodo.'-01')->locale('es')->translatedFormat('F Y') }}</span>
                                        <strong class="text-rose-600 dark:text-rose-400">S/ {{ number_format($importe, 2) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/25 dark:text-emerald-200">Sin mensualidades emitidas con saldo pendiente.</p>
                        @endif
                        @if ($resumenFicha['internet_sin_periodo'] > 0)
                            <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">También hay S/ {{ number_format($resumenFicha['internet_sin_periodo'], 2) }} de cargos de Internet confirmados sin periodo mensual asociado.</p>
                        @endif
                    </section>
                    <section class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        <h3 class="font-bold">Último pago registrado</h3>
                        @if ($ultimoPago)
                            <p class="mt-4 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">S/ {{ number_format((float) $ultimoPago->monto, 2) }}</p>
                            <p class="mt-1 font-semibold">{{ \Carbon\Carbon::parse($ultimoPago->fecha_pago)->format('d/m/Y') }}</p>
                            <p class="mt-2 text-xs text-zinc-500">Categoría: {{ ucfirst($ultimoPago->categoria ?? 'Sin registrar') }} · Medio: {{ ucfirst($ultimoPago->medio ?? 'Sin registrar') }}</p>
                        @else
                            <p class="mt-4 text-sm text-zinc-500">Sin pagos registrados en este sistema.</p>
                        @endif
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Ver historial</a>
                            <a href="{{ route('cobranza.mensualidades') }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Mensualidades</a>
                        </div>
                    </section>
                </div>
            </div>

            {{-- DATOS PERSONALES / GPS --}}
            <div x-show="tab === 'datos'" x-cloak role="tabpanel" class="space-y-5 p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-lg font-extrabold">Datos y ubicación</h2><p class="text-xs text-zinc-500">Datos guardados en la ficha del cliente.</p></div>
                    <a href="{{ route('clientes.index', ['cliente' => $cliente->id]) }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-bold dark:border-zinc-700">Editar cliente</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Código' => $cliente->codigo,
                        'Nombre' => $cliente->nombre,
                        'Documento' => $cliente->documento,
                        'Teléfono' => $cliente->telefono,
                        'Sede' => $ubicacion,
                        'Sector' => $cliente->sector,
                        'Dirección' => $cliente->direccion,
                        'Instalación' => $cliente->fecha_instalacion?->format('d/m/Y'),
                        'Baja definitiva' => $cliente->fecha_baja?->format('d/m/Y'),
                        'Coordenadas GPS' => $cliente->latitud !== null && $cliente->longitud !== null ? $cliente->latitud.', '.$cliente->longitud : null,
                    ] as $rotulo => $valor)
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/50"><p class="text-xs text-zinc-500">{{ $rotulo }}</p><p class="mt-1 break-words text-sm font-semibold">{{ $valor ?: 'Sin registrar' }}</p></div>
                    @endforeach
                </div>
                @if ($cliente->latitud !== null && $cliente->longitud !== null)
                    <a href="{{ 'https://www.google.com/maps?q='.rawurlencode((string)$cliente->latitud.','.(string)$cliente->longitud) }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Abrir ubicación en Google Maps ↗</a>
                @endif
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs font-bold text-zinc-500">Observaciones</p><p class="mt-2 whitespace-pre-line text-sm">{{ $cliente->observaciones ?: 'Sin observaciones' }}</p></div>
            </div>
        </section>
    </div>
</x-layouts::app>
