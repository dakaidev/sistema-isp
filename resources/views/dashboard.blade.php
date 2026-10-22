<x-layouts::app :title="'Inicio | Sistema ISP'">
    @php
        // Estadísticas de clientes, cargos y pagos registrados realmente en MySQL.
        $db = \Illuminate\Support\Facades\DB::class;
        $totalClientes = $db::table('clientes')->count();
        $totalServicios = $db::table('servicios')->count();
        $tarifasConfiguradas = $db::table('isp_configuracion_mensual')->count();
        $mensualidadesHabilitadas = $db::table('isp_configuracion_mensual')->where('habilitado', 1)->count();
        $mensualidadesEmitidas = $db::table('isp_mensualidades_generadas')->count();
        $pagosRegistrados = $db::table('isp_pagos')->count();
        $hoyPeru = \Illuminate\Support\Carbon::now('America/Lima')->locale('es')->translatedFormat('l, d \\d\\e F \\d\\e Y');
    @endphp

    <div class="mx-auto w-full max-w-7xl space-y-6 pb-12 text-slate-900 dark:text-slate-100">
        {{-- CABECERA --}}
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-800 p-6 text-white shadow-xl shadow-blue-950/10 md:p-9">
            <div class="pointer-events-none absolute -right-24 -top-28 size-80 rounded-full bg-blue-400/10 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-0 right-36 size-44 rounded-full bg-cyan-300/10 blur-3xl"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-5">
                <div class="max-w-2xl space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold tracking-wider text-blue-100">
                        <span class="inline-block size-2 rounded-full bg-emerald-400"></span>
                        SISTEMA ISP · PANEL DE CONTROL
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">Bienvenido a tu ISP</h1>
                    <p class="max-w-xl text-sm leading-relaxed text-blue-100 md:text-base">Tus clientes, mensualidades y cobranzas desde un solo lugar. Trabaja abonado por abonado, con información verificada.</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-left backdrop-blur-sm">
                    <p class="text-xs font-medium uppercase tracking-wider text-blue-200">Hoy en Perú</p>
                    <p class="mt-1 text-sm font-semibold capitalize">{{ $hoyPeru }}</p>
                </div>
            </div>
            <div class="relative mt-7 flex flex-wrap gap-3">
                <a href="{{ route('cobranza.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-blue-950 shadow-sm transition hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Ir a Cobranza <span aria-hidden="true">→</span></a>
                <a href="{{ route('clientes.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Buscar cliente <span aria-hidden="true">↗</span></a>
            </div>
        </section>

        {{-- MÉTRICAS: valores reales de MySQL, no proyecciones --}}
        <section aria-label="Resumen de la base de datos" class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Clientes registrados</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight">{{ number_format($totalClientes) }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400">Incluye registros de prueba</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Servicios registrados</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight">{{ number_format($totalServicios) }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400">Conexiones en el sistema</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-zinc-400">Tarifas guardadas</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight">{{ number_format($tarifasConfiguradas) }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400">Algunas son provisionales</p>
            </div>
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Cobro mensual habilitado</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-800 dark:text-emerald-300">{{ number_format($mensualidadesHabilitadas) }}</p>
                <p class="mt-2 text-xs text-emerald-800/80 dark:text-emerald-300/80">Confirmados manualmente</p>
            </div>
        </section>

        {{-- ACCESOS DIRECTOS --}}
        <section class="space-y-3">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold tracking-tight">¿Qué quieres hacer hoy?</h2>
                <span class="text-xs text-slate-500 dark:text-zinc-400">Elige un módulo para continuar</span>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('clientes.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-500">
                    <div class="flex items-start justify-between"><span class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-xl text-blue-600 dark:bg-blue-950 dark:text-blue-300">♙</span><span class="text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-500">→</span></div>
                    <h3 class="mt-4 text-base font-bold">Clientes</h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-zinc-400">Buscar, registrar y editar los datos de tus abonados.</p>
                </a>
                <a href="{{ route('servicios.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-500">
                    <div class="flex items-start justify-between"><span class="flex size-11 items-center justify-center rounded-xl bg-violet-50 text-xl text-violet-600 dark:bg-violet-950 dark:text-violet-300">◉</span><span class="text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-500">→</span></div>
                    <h3 class="mt-4 text-base font-bold">Servicios</h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-zinc-400">Fibra, inalámbrico y detalles técnicos de cada conexión.</p>
                </a>
                <a href="{{ route('cobranza.mensualidades') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-500">
                    <div class="flex items-start justify-between"><span class="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-xl text-amber-600 dark:bg-amber-950 dark:text-amber-300">▤</span><span class="text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-500">→</span></div>
                    <h3 class="mt-4 text-base font-bold">Mensualidades</h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-zinc-400">Confirmar tarifa y habilitar facturación cliente por cliente.</p>
                </a>
                <a href="{{ route('cobranza.index') }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-500">
                    <div class="flex items-start justify-between"><span class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-xl text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300">S/</span><span class="text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-500">→</span></div>
                    <h3 class="mt-4 text-base font-bold">Cobranza y pagos</h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-zinc-400">Revisar cargos, registrar pagos y consultar vencimientos.</p>
                </a>
            </div>
        </section>

        {{-- FLUJO DE OPERACIÓN --}}
        <section class="grid gap-4 xl:grid-cols-[1.35fr_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
                <div class="flex items-center justify-between gap-2"><h2 class="text-lg font-bold">Tu rutina de trabajo</h2><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">Manual y segura</span></div>
                <div class="mt-5 space-y-4">
                    <div class="flex items-start gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">1</span><div><p class="text-sm font-bold">Busca al cliente</p><p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Confirma nombre, estado y si actualmente recibe internet.</p></div></div>
                    <div class="flex items-start gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">2</span><div><p class="text-sm font-bold">Revisa su mensualidad</p><p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Corrige el precio provisional, el inicio y habilita solo al abonado correcto.</p></div></div>
                    <div class="flex items-start gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">3</span><div><p class="text-sm font-bold">Ve a Cobranza</p><p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Comprueba los cargos, registra pagos y revisa saldos vencidos.</p></div></div>
                </div>
                <a href="{{ route('cobranza.mensualidades') }}" class="mt-6 inline-flex min-h-10 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-700">Comenzar con un cliente <span class="ml-2">→</span></a>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
                <h2 class="text-lg font-bold">Estado de la cobranza</h2>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-zinc-800"><p class="text-xs text-slate-500 dark:text-zinc-400">Mensualidades emitidas</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($mensualidadesEmitidas) }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-zinc-800"><p class="text-xs text-slate-500 dark:text-zinc-400">Pagos registrados</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($pagosRegistrados) }}</p></div>
                </div>
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    <span class="font-bold">Recuerda:</span> solo los cargos con saldo pendiente determinan deuda; los pagos se contabilizan una vez según su fecha.
                </div>
                <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-zinc-400">Este panel no activa cargos ni suspende conexiones. Solo consulta y te guía a tus módulos actuales.</p>
            </div>
        </section>
    </div>
</x-layouts::app>
