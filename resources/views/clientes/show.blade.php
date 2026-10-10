
<x-layouts::app :title="'Ficha del Cliente | Sistema ISP'">

    <div class="mx-auto max-w-7xl space-y-6 pb-10">

        <!-- ENCABEZADO -->
        <div class="rounded-3xl bg-gradient-to-r
                    from-slate-900 to-blue-900 p-7 text-white">

            <a href="{{ route('clientes.index') }}"
               wire:navigate
               class="text-sm font-medium text-blue-200
                      hover:text-white">
                ← Volver a Clientes
            </a>

            <p class="mt-6 text-xs font-bold uppercase
                      tracking-widest text-blue-200">
                Sistema ISP / Ficha del cliente
            </p>

            <h1 class="mt-2 text-3xl font-bold">
                {{ $cliente->nombre }}
            </h1>

            <p class="mt-2 text-sm text-blue-100">
                Código: {{ $cliente->codigo }}
            </p>

        </div>

        <!-- RESUMEN -->
        @php
            $serviciosActivos = $cliente->servicios
                ->where('estado', 'activo');
        @endphp

        <div class="grid gap-4 sm:grid-cols-3">

            <div class="rounded-2xl border border-zinc-200
                        bg-white p-5 dark:border-zinc-800
                        dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">
                    Total de servicios
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ $cliente->servicios->count() }}
                </h2>
            </div>

            <div class="rounded-2xl border border-zinc-200
                        bg-white p-5 dark:border-zinc-800
                        dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">
                    Servicios activos
                </p>

                <h2 class="mt-2 text-3xl font-bold
                           text-green-600">
                    {{ $serviciosActivos->count() }}
                </h2>
            </div>

            <div class="rounded-2xl border border-zinc-200
                        bg-white p-5 dark:border-zinc-800
                        dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">
                    Suma de tarifas activas / mes
                </p>

                <h2 class="mt-2 text-3xl font-bold
                           text-blue-600">
                    S/ {{ number_format(
                        (float) $serviciosActivos->sum('tarifa_mensual'),
                        2
                    ) }}
                </h2>
            </div>

        </div>

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
                    'Sector / Comunidad' => $cliente->sector,
                    'Dirección' => $cliente->direccion,
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
                                    Tarifa mensual
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
                                {{ $servicio->fecha_activacion->format('d/m/Y') }}
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
                        Este cliente todavía no tiene
                        servicios registrados.
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</x-layouts::app>
