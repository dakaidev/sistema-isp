<div class="space-y-5 rounded-2xl border border-blue-400/40 bg-white p-4 shadow-sm dark:bg-zinc-900 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-blue-700 dark:text-blue-300">Ficha unificada del cliente</p>
            <h3 class="mt-1 text-lg font-bold">{{ $perfilClienteNombre }}</h3>
            <p class="mt-1 text-xs text-zinc-500">La facturación es independiente del registro técnico. Guardar un bloque no modifica el otro.</p>
        </div>
        <button type="button" wire:click="cerrarPerfil" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">Cerrar</button>
    </div>

    <section class="rounded-2xl border border-sky-600/30 bg-sky-500/5 p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h4 class="font-bold">Sede del cliente</h4>
                <p class="mt-1 text-xs text-zinc-500">Upina y Calasuca tienen servidores independientes. Esta asignación es administrativa; no afecta deudas ni pagos.</p>
                <p class="mt-2 text-sm font-semibold">
                    Actual: {{ $perfilSede === 'upina' ? 'Upina' : ($perfilSede === 'calasuca' ? 'Calasuca' : 'Sin sede asignada') }}
                </p>
            </div>
        </div>
        <form wire:submit="guardarSede" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-semibold" for="perfil-sede">Sede / servidor</label>
                <select id="perfil-sede" wire:model="perfilSede" required
                    class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                    <option value="">Seleccionar sede...</option>
                    <option value="upina">Upina — servidor de Upina</option>
                    <option value="calasuca">Calasuca — servidor de Calasuca</option>
                </select>
                @error('perfilSede') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="guardarSede"
                class="rounded-xl bg-sky-700 px-5 py-3 text-sm font-bold text-white hover:bg-sky-800 disabled:opacity-50">Guardar sede</button>
        </form>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-emerald-600/30 p-4 dark:bg-emerald-950/10 sm:p-5">
            <h4 class="text-lg font-bold">1. Facturación mensual</h4>
            <p class="mt-1 text-xs leading-5 text-zinc-500">Este es el importe oficial que usa el generador de mensualidades. No modifica cargos ni pagos anteriores.</p>
            <form wire:submit="guardarFacturacion" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Tarifa por mes (S/)</label>
                    <input type="number" step="0.01" min="0.01" required wire:model="perfilTarifa" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                    @error('perfilTarifa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Inicio de cobro</label>
                    <input type="date" required wire:model="perfilInicio" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                    @error('perfilInicio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <input type="checkbox" wire:model="perfilHabilitado" class="mt-1 size-4" />
                    <span class="text-sm"><strong class="block">Habilitar facturación</strong><span class="text-xs text-zinc-500">Actívala solo si corresponde cobrar mensualidades al cliente. No cobra de inmediato por este botón.</span></span>
                </label>
                @error('perfilHabilitado') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="guardarFacturacion" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50">Guardar facturación</button>
            </form>
        </section>

        <section class="rounded-2xl border border-blue-600/30 p-4 dark:bg-blue-950/10 sm:p-5">
            <h4 class="text-lg font-bold">2. Conexión técnica</h4>
            <p class="mt-1 text-xs leading-5 text-zinc-500">Fibra o inalámbrico, plan, velocidad y estado técnico. Registrar una conexión no crea otra mensualidad.</p>
            @if ($perfilCantidadServicios > 1)
                <p class="mt-4 rounded-xl border border-amber-400/50 bg-amber-500/10 p-4 text-sm">Este cliente tiene {{ $perfilCantidadServicios }} conexiones. Para no cambiar la equivocada, edítalas individualmente desde <a href="{{ route('servicios.index') }}" class="font-bold underline">Servicios de Internet</a>.</p>
            @else
                @if (!$perfilServicioId)
                    <p class="mt-4 rounded-xl bg-zinc-100 p-3 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">Aún no tiene ficha técnica. Si conoces la conexión, complétala y presiona «Registrar conexión». No se creará automáticamente.</p>
                @endif
                <form wire:submit="guardarConexion" class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Tipo de conexión</label>
                        <select wire:model="perfilTipoConexion" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            <option value="fibra">Fibra óptica</option><option value="inalambrico">Internet inalámbrico</option>
                        </select>
                        @error('perfilTipoConexion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Plan de Internet (opcional)</label>
                        <select wire:model.change="perfilPlanElegido" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            <option value="personalizado">Personalizado / tarifa anterior</option>
                            @foreach (\App\Support\PlanesInternet::CATALOGO as $clave => $plan)
                                <option value="{{ $clave }}">{{ $plan['nombre'] }} — {{ $plan['velocidad'] }} Mbps — S/ {{ number_format($plan['precio'], 2) }} al mes</option>
                            @endforeach
                        </select>
                        @error('perfilPlanElegido') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if (isset(\App\Support\PlanesInternet::CATALOGO[$perfilPlanElegido]))
                            <p class="mt-2 text-xs text-zinc-500">Este plan propone una velocidad y un precio. <strong>Elegirlo no cambia lo que se cobra</strong> ni modifica las mensualidades ya emitidas.</p>
                            <button type="button" wire:click="proponerTarifaPlan" class="mt-2 rounded-lg border border-emerald-600 px-3 py-2 text-xs font-semibold text-emerald-600">Copiar precio propuesto a Facturación (sin guardar)</button>
                        @endif
                    </div>
                    @if ($perfilPlanElegido === 'personalizado')
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-semibold">Nombre del plan (opcional)</label>
                                <input type="text" wire:model="perfilPlan" placeholder="Ej. Hogar" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                                @error('perfilPlan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold">Velocidad Mbps (opcional)</label>
                                <input type="number" min="1" wire:model="perfilVelocidad" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                                @error('perfilVelocidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">Nombre: {{ $perfilPlan }} · Velocidad: {{ $perfilVelocidad }} Mbps.</p>
                    @endif
                    {{-- La fecha de activación técnica es un dato avanzado, no la fecha de instalación ni el inicio de cobro. --}}
                    <details class="rounded-xl border border-zinc-300 p-3 dark:border-zinc-700" @if ($errors->has('perfilFechaActivacion')) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold">Datos técnicos opcionales</summary>
                        <div class="mt-3">
                            @if (!$perfilServicioId)
                                <label class="mb-1 block text-sm font-semibold">Fecha de activación técnica (opcional)</label>
                                <input type="date" wire:model="perfilFechaActivacion" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700" />
                                <p class="mt-2 text-xs text-zinc-500">Solo si conoces el día real. Puedes dejarlo vacío; no afecta el inicio de cobro ni la instalación.</p>
                                @error('perfilFechaActivacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-xs text-zinc-500">Activación técnica registrada: {{ $perfilFechaActivacion ? \Carbon\Carbon::parse($perfilFechaActivacion)->format('d/m/Y') : 'Sin fecha' }}. No se modifica desde aquí.</p>
                            @endif
                        </div>
                    </details>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Estado de conexión</label>
                        <select wire:model="perfilEstado" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 text-sm dark:border-zinc-700">
                            <option value="activo">Activo</option><option value="suspendido">Suspendido</option>
                        </select>
                        @error('perfilEstado') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardarConexion" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">{{ $perfilServicioId ? 'Guardar datos técnicos' : 'Registrar conexión' }}</button>
                </form>
            @endif
        </section>
    </div>
    <p class="rounded-xl bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-200">Importante: una tarifa nueva no cambia deudas ya emitidas. El estado «Activo» de la conexión no habilita automáticamente el cobro. Para cobrar un mes, usa Cobranza.</p>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('clientes.show', $perfilClienteId) }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-xs font-semibold dark:border-zinc-700">Ver ficha completa</a>
        <a href="{{ url('/cobranza/ficha/'.$perfilClienteId) }}" class="rounded-xl border border-emerald-500 px-4 py-2 text-xs font-semibold text-emerald-600">Ir a Cobranza</a>
    </div>
</div>
