<div class="mx-auto max-w-6xl space-y-5 pb-12">
    <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-slate-950 to-blue-900 px-6 py-6 text-white">
        <div>
            <h1 class="text-2xl font-extrabold">Usuarios y permisos</h1>
            <p class="mt-1 text-sm text-blue-100">Solo el administrador crea cuentas y decide qué pueden hacer.</p>
        </div>
        <button type="button" wire:click="nuevo" class="rounded-xl bg-blue-500 px-4 py-3 text-sm font-bold hover:bg-blue-400">+ Nuevo usuario</button>
    </header>

    @if (session('usuarios_mensaje'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">{{ session('usuarios_mensaje') }}</div>
    @endif
    @error('estado') <div class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-red-600">{{ $message }}</div> @enderror

    <div class="grid gap-3 md:grid-cols-4">
        @foreach($nombresRoles as $codigo => $etiqueta)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500">{{ $etiqueta }}</p>
                <p class="mt-2 text-2xl font-bold">{{ \App\Models\User::where('rol', $codigo)->count() }}</p>
            </div>
        @endforeach
    </div>

    @if ($mostrarFormulario)
        <section class="rounded-2xl border border-blue-500/30 bg-white p-5 dark:bg-zinc-900 sm:p-6">
            <h2 class="mb-5 text-lg font-bold">{{ $editando ? 'Editar cuenta' : 'Crear cuenta de trabajador' }}</h2>
            <form wire:submit="guardar" class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Nombre</label>
                    <input wire:model="nombre" autocomplete="off" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700" />
                    @error('nombre') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Correo de acceso</label>
                    <input type="email" wire:model="correo" autocomplete="off" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700" />
                    @error('correo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Rol</label>
                    <select wire:model="rol" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach($nombresRoles as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                    @error('rol') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">{{ $editando ? 'Contraseña nueva (opcional)' : 'Contraseña inicial (mínimo 12 caracteres)' }}</label>
                    <input type="password" wire:model="clave" autocomplete="new-password" class="w-full rounded-xl border border-zinc-300 bg-transparent px-4 py-3 dark:border-zinc-700" />
                    @error('clave') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="activo" /> Cuenta activa</label>
                <div class="flex justify-end gap-2 md:col-span-2">
                    <button type="button" wire:click="cancelar" class="rounded-xl border border-zinc-500 px-4 py-2">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 font-semibold text-white hover:bg-blue-700">Guardar usuario</button>
                </div>
            </form>
        </section>
    @endif

    <section class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap justify-between gap-3">
            <h2 class="text-lg font-bold">Cuentas registradas</h2>
            <input wire:model.live.debounce.300ms="buscar" placeholder="Buscar nombre o correo..." class="min-w-56 rounded-xl border border-zinc-300 bg-transparent px-4 py-2 text-sm dark:border-zinc-700" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-zinc-300 text-xs uppercase text-zinc-500 dark:border-zinc-700"><th class="py-3">Cuenta</th><th class="py-3">Rol</th><th class="py-3">Estado</th><th class="py-3 text-right">Acciones</th></tr></thead>
                <tbody>
                @foreach($usuarios as $u)
                    <tr class="border-b border-zinc-200 dark:border-zinc-800" wire:key="usuario-{{ $u->id }}">
                        <td class="py-3"><p class="font-semibold">{{ $u->name }}</p><p class="text-xs text-zinc-500">{{ $u->email }}</p></td>
                        <td class="py-3">{{ $nombresRoles[$u->rol] ?? $u->rol }}</td>
                        <td class="py-3"><span class="rounded-lg px-2 py-1 text-xs font-bold {{ $u->activo ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300' : 'bg-red-500/15 text-red-600 dark:text-red-300' }}">{{ $u->activo ? 'Activo' : 'Bloqueado' }}</span></td>
                        <td class="py-3 text-right"><button type="button" wire:click="editar({{ $u->id }})" class="mr-2 text-blue-600 hover:underline dark:text-blue-300">Editar</button><button type="button" wire:click="cambiarEstado({{ $u->id }})" class="text-zinc-500 hover:underline">{{ $u->activo ? 'Bloquear' : 'Activar' }}</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $usuarios->links() }}
    </section>
    <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs text-zinc-500">
        Los permisos se comprueban en el servidor. La cuenta visualizadora es solo lectura; el técnico no puede cambiar tarifas; el cobrador no puede corregir cargos ni administrar usuarios. La asignación por localidad se implementará aparte para garantizar su cumplimiento en todas las consultas.
    </div>
</div>
