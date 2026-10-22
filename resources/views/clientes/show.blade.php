
<x-layouts::app :title="'Ficha del Cliente | Sistema ISP'">

    <div class="mx-auto max-w-7xl space-y-6 pb-10">

        {{-- Mejora 02: resumen unificado de consulta. Sin registrar cargos ni pagos. --}}
        @php
            $facturacion = $resumenFicha['configuracion'];
            $serviciosActivos = $cliente->servicios->where('estado', 'activo');
            $totalServicios = $cliente->servicios->count();
            $tieneServicioSuspendido = $cliente->servicios->contains(fn ($s) => $s->estado === 'suspendido');
            $estadoConexion = $totalServicios === 0 ? 'Sin ficha técnica'
                : ($serviciosActivos->count() > 0 && $tieneServicioSuspendido ? 'Estado mixto'
                : ($serviciosActivos->count() > 0 ? 'Activo'
                : ($tieneServicioSuspendido ? 'Suspendido' : 'Sin estado definido')));
            $estadoFacturacion = !$facturacion ? 'Sin configurar'
                : ($facturacion->habilitado ? 'Habilitada' : 'Deshabilitada');
            $mesesPendientes = $resumenFicha['meses_pendientes'];
            $ultimoPago = $resumenFicha['ultimo_pago'];
        @endphp

        <!-- ENCABEZADO Y ATAJOS -->
        <div class="rounded-3xl bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 p-6 text-white shadow-sm sm:p-7">
            <a href="{{ route('clientes.index') }}" wire:navigate
               class="text-sm font-semibold text-blue-200 hover:text-white">← Volver a Clientes</a>
            <div class="mt-5 flex flex-wrap items-start justify-between gap-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-200">Sistema ISP / Ficha unificada</p>
                    <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">{{ $cliente->nombre }}</h1>
                    <p class="mt-2 text-sm text-blue-100">
                        {{ $cliente->codigo }} · {{ $cliente->sede === 'upina' ? 'Upina' : ($cliente->sede === 'calasuca' ? 'Calasuca' : 'Sede sin asignar') }}
                        · {{ $cliente->telefono ?: 'Sin teléfono' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-sm font-semibold">
                    <a href="{{ route('clientes.index', ['cliente' => $cliente->id]) }}"
                       class="rounded-xl border border-white/30 px-4 py-2.5 text-white hover:bg-white/10">Editar / configurar</a>
                    <a href="{{ route('cobranza.ficha', $cliente->id) }}"
                       class="rounded-xl bg-white px-4 py-2.5 text-blue-950 hover:bg-blue-100">Ver cobros y registrar pago →</a>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5">Internet: {{ $estadoConexion }}</span>
                <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5">Facturación: {{ $estadoFacturacion }}</span>
                @if ($cliente->fecha_baja)
                    <span class="rounded-full border border-amber-300/50 bg-amber-300/20 px-3 py-1.5 text-amber-100">Baja definitiva registrada</span>
                @endif
            </div>
        </div>

        <!-- INDICADORES PRINCIPALES -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Tarifa oficial mensual</p>
                <p class="mt-2 text-2xl font-extrabold text-blue-600 dark:text-blue-400">
                    {{ $facturacion && $facturacion->tarifa_mensual !== null ? 'S/ '.number_format((float) $facturacion->tarifa_mensual, 2) : 'Sin tarifa' }}
                </p>
                <p class="mt-2 text-xs text-zinc-500">{{ $estadoFacturacion }} · {{ $facturacion?->inicio_cobro ? 'Desde '.\Carbon\Carbon::parse($facturacion->inicio_cobro)->format('d/m/Y') : 'Sin fecha de inicio' }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Deuda confirmada pendiente</p>
                <p class="mt-2 text-2xl font-extrabold {{ $resumenFicha['saldos']['total'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    S/ {{ number_format($resumenFicha['saldos']['total'], 2) }}
                </p>
                <p class="mt-2 text-xs text-zinc-500">Internet S/ {{ number_format($resumenFicha['saldos']['internet'], 2) }} · Equipo S/ {{ number_format($resumenFicha['saldos']['equipo'], 2) }} · Otros S/ {{ number_format($resumenFicha['saldos']['otros'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Internet vencido confirmado</p>
                <p class="mt-2 text-2xl font-extrabold {{ $resumenFicha['saldos']['vencido_internet'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white' }}">
                    S/ {{ number_format($resumenFicha['saldos']['vencido_internet'], 2) }}
                </p>
                <p class="mt-2 text-xs text-zinc-500">No genera suspensión ni cortes automáticos.</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Conexiones documentadas</p>
                <p class="mt-2 text-2xl font-extrabold">{{ $totalServicios }}</p>
                <p class="mt-2 text-xs text-zinc-500">{{ $serviciosActivos->count() }} activas · {{ $estadoConexion }}</p>
            </div>
        </div>

        <!-- PERIODOS PENDIENTES Y ULTIMO PAGO -->
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold">Mensualidades pendientes</h2>
                        <p class="mt-1 text-xs text-zinc-500">Solo periodos con cargo emitido y saldo sin aplicar.</p>
                    </div>
                    <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400">Ver detalle →</a>
                </div>
                @if (count($mesesPendientes))
                    <div class="mt-4 space-y-2">
                        @foreach (array_slice($mesesPendientes, 0, 6, true) as $periodo => $importe)
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-800/70">
                                <span class="font-semibold capitalize">{{ \Carbon\Carbon::parse($periodo.'-01')->locale('es')->translatedFormat('F Y') }}</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">S/ {{ number_format($importe, 2) }}</span>
                            </div>
                        @endforeach
                        @if (count($mesesPendientes) > 6)
                            <p class="text-xs text-zinc-500">Y {{ count($mesesPendientes) - 6 }} meses más con saldo. Ver todos en Cobranza.</p>
                        @endif
                    </div>
                @else
                    <p class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-300">No hay mensualidades emitidas con saldo pendiente.</p>
                @endif
                @if ($resumenFicha['internet_sin_periodo'] > 0)
                    <p class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
                        También hay S/ {{ number_format($resumenFicha['internet_sin_periodo'], 2) }} de cargos de Internet sin periodo de mensualidad generada. Revísalos en Cobranza.
                    </p>
                @endif
                <p class="mt-3 text-xs text-zinc-500">Los apuntes históricos sin cargo confirmado no se consideran deuda nueva en este resumen.</p>
            </section>
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold">Último pago registrado</h2>
                        <p class="mt-1 text-xs text-zinc-500">Según la fecha real registrada en Cobranza.</p>
                    </div>
                    <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400">Historial →</a>
                </div>
                @if ($ultimoPago)
                    <p class="mt-5 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">S/ {{ number_format((float) $ultimoPago->monto, 2) }}</p>
                    <p class="mt-2 font-semibold">{{ \Carbon\Carbon::parse($ultimoPago->fecha_pago)->format('d/m/Y') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">Categoría: {{ ucfirst($ultimoPago->categoria) }} · Medio: {{ ucfirst($ultimoPago->medio) }}</p>
                @else
                    <p class="mt-5 rounded-xl bg-zinc-100 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">No hay pagos nuevos registrados en el sistema para este cliente.</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <a href="{{ route('cobranza.ficha', $cliente->id) }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Ir a registrar pago</a>
                    <a href="{{ route('cobranza.mensualidades') }}" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Mensualidades</a>
                </div>
            </section>
        </div>

        @if (($serviciosActivos->isEmpty() && $tieneServicioSuspendido && $facturacion?->habilitado) || ($serviciosActivos->isNotEmpty() && $facturacion && !$facturacion->habilitado))
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
                <strong>Revisar configuración:</strong> El estado técnico de la conexión y el estado de facturación no coinciden. Esto no cambia la facturación ni realiza cortes automáticamente.
                <a href="{{ route('clientes.index', ['cliente' => $cliente->id]) }}" class="ml-2 font-bold underline">Revisar cliente →</a>
            </div>
        @endif

        <!-- DATOS PERSONALES -->
        <div class="rounded-2xl border border-zinc-200
                    bg-white p-6 shadow-sm
                    dark:border-zinc-800 dark:bg-zinc-900">

            <h2 class="text-xl font-bold">
                Información del cliente
            </h2>

            <div class="mt-6 grid gap-5
                        sm:grid-cols-2 lg:grid-cols-3">

                @foreach ([
                    'Código' => $cliente->codigo,
                    'Nombre completo' => $cliente->nombre,
                    'Documento' => $cliente->documento,
                    'Celular' => $cliente->telefono,
                    'Sede / Servidor' => $cliente->sede === 'upina' ? 'Upina' : ($cliente->sede === 'calasuca' ? 'Calasuca' : 'Sin asignar'),
                    'Ubicación GPS' => $cliente->latitud !== null && $cliente->longitud !== null
                        ? (string) $cliente->latitud . ', ' . (string) $cliente->longitud
                        : null,
                    'Dirección' => $cliente->direccion,
                    'Fecha de instalación' => $cliente->fecha_instalacion?->format('d/m/Y'),
                    'Fecha de baja definitiva' => $cliente->fecha_baja?->format('d/m/Y'),
                ] as $etiqueta => $valor)

                    <div>
                        <p class="text-xs font-medium text-zinc-500">
                            {{ $etiqueta }}
                        </p>

                        <p class="mt-2 font-semibold">
                            {{ $valor ?: 'Sin registrar' }}
                        </p>
                    </div>

                @endforeach

            </div>

            @if ($cliente->latitud !== null && $cliente->longitud !== null)
                <div class="mt-5">
                    <a href="{{ 'https://www.google.com/maps?q='.rawurlencode((string) $cliente->latitud.','.(string) $cliente->longitud) }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex rounded-xl border border-blue-400 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-300">
                        Ver ubicación del cliente en Google Maps
                    </a>
                </div>
            @endif

            <div class="mt-6 border-t border-zinc-200 pt-5
                        dark:border-zinc-800">

                <p class="text-xs font-medium text-zinc-500">
                    Observaciones
                </p>

                <p class="mt-2 whitespace-pre-line text-sm">
                    {{ $cliente->observaciones ?: 'Sin observaciones' }}
                </p>

            </div>

        </div>

        <!-- CONEXIONES DEL CLIENTE -->
        <div class="rounded-2xl border border-zinc-200
                    bg-white p-6 shadow-sm
                    dark:border-zinc-800 dark:bg-zinc-900">

            <div class="flex flex-wrap items-center
                        justify-between gap-3">

                <div>
                    <h2 class="text-xl font-bold">
                        Conexiones de Internet
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Servicios contratados por este cliente.
                    </p>
                </div>

                <a href="{{ route('servicios.index') }}"
                   wire:navigate
                   class="rounded-xl bg-blue-600
                          px-4 py-2 text-sm font-semibold
                          text-white hover:bg-blue-700">
                    Gestionar servicios
                </a>

            </div>

            <div class="mt-6 space-y-4">

                @forelse ($cliente->servicios as $servicio)

                    <div class="rounded-2xl border
                                border-zinc-200 p-5
                                dark:border-zinc-700">

                        <div class="flex flex-wrap items-start
                                    justify-between gap-4">

                            <div>
                                <h3 class="text-lg font-bold">
                                    {{ $servicio->tipo_conexion === 'fibra'
                                        ? 'Fibra óptica'
                                        : 'Internet inalámbrico' }}
                                </h3>

                                <p class="mt-1 text-sm text-zinc-500">
                                    {{ $servicio->nombre_plan ?: 'Sin nombre de plan' }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xl font-bold
                                          text-blue-600">
                                    S/ {{ number_format(
                                        (float) $servicio->tarifa_mensual,
                                        2
                                    ) }}
                                </p>

                                <p class="text-xs text-zinc-500">
                                    Tarifa inicial técnica (referencia)
                                </p>
                            </div>

                        </div>

                        <div class="mt-4">

                            @if ($servicio->estado === 'activo')
                                <span class="rounded-full bg-green-100
                                             px-3 py-1 text-xs font-bold
                                             text-green-700">
                                    Activo
                                </span>
                            @else
                                <span class="rounded-full bg-amber-100
                                             px-3 py-1 text-xs font-bold
                                             text-amber-700">
                                    Suspendido
                                </span>
                            @endif

                        </div>

                        <div class="mt-5 grid gap-4 border-t
                                    border-zinc-200 pt-4
                                    text-sm sm:grid-cols-2
                                    dark:border-zinc-700">

                            <p>
                                <span class="text-zinc-500">
                                    Velocidad:
                                </span>
                                {{ $servicio->velocidad_mbps
                                    ? $servicio->velocidad_mbps . ' Mbps'
                                    : 'Sin registrar' }}
                            </p>

                            <p>
                                <span class="text-zinc-500">
                                    Activación:
                                </span>
                                {{ $servicio->fecha_activacion?->format('d/m/Y') ?? 'Sin fecha' }}
                            </p>

                            <p>
                                <span class="text-zinc-500">IP:</span>
                                {{ $servicio->ip_asignada ?: 'Sin registrar' }}
                            </p>

                            <p>
                                <span class="text-zinc-500">MAC:</span>
                                {{ $servicio->mac_equipo ?: 'Sin registrar' }}
                            </p>

                            <p class="sm:col-span-2">
                                <span class="text-zinc-500">
                                    Dirección de instalación:
                                </span>
                                {{ $servicio->direccion_instalacion ?: 'Sin registrar' }}
                            </p>

                        </div>

                        @if ($servicio->observaciones)
                            <p class="mt-4 text-sm text-zinc-500">
                                {{ $servicio->observaciones }}
                            </p>
                        @endif

                    </div>

                @empty

                    <div class="rounded-xl bg-zinc-100 p-8
                                text-center text-zinc-500
                                dark:bg-zinc-800">
                        Este cliente no tiene conexión técnica documentada.
                        Esto NO significa que carezca de tarifa o facturación mensual.
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</x-layouts::app>
