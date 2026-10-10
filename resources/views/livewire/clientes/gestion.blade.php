<div class="mx-auto w-full max-w-7xl space-y-6 pb-10">

    <!-- ENCABEZADO -->
    <div class="rounded-3xl bg-gradient-to-r
                from-slate-900 to-blue-900 p-7 text-white">

        <p class="text-xs font-bold uppercase tracking-widest
                  text-blue-200">
            Sistema ISP / Administración
        </p>

        <h1 class="mt-3 text-3xl font-bold">
            Gestión de Clientes
        </h1>

        <p class="mt-2 text-sm text-blue-100">
            Registra, consulta y organiza los clientes
            de tu negocio de internet.
        </p>
    </div>

    <!-- MENSAJE DE ÉXITO -->
    @if (session()->has('mensaje'))
        <div class="rounded-xl border border-green-300
                    bg-green-50 p-4 text-sm font-medium
                    text-green-800 dark:border-green-800
                    dark:bg-green-950 dark:text-green-200">
            {{ session('mensaje') }}
        </div>
    @endif

    <!-- ESTADÍSTICA -->
    <div class="rounded-2xl border border-zinc-200
                bg-white p-6 shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <p class="text-sm font-medium text-zinc-500">
            Total de clientes registrados
        </p>

        <h2 class="mt-2 text-4xl font-bold
                   text-blue-600 dark:text-blue-400">
            {{ $totalClientes }}
        </h2>

        <p class="mt-2 text-xs text-zinc-500">
            Registros almacenados en nuestra base de datos.
        </p>
    </div>

    <!-- FORMULARIO -->
    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="border-b border-zinc-200 p-6
                    dark:border-zinc-800">

            <h2 class="text-xl font-bold">
                {{ $clienteEditandoId !== null 
                    ? 'Editar cliente'
                    : 'Nuevo cliente'}}
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Completa los datos del abonado.
                Los campos con * son obligatorios.
            </p>
        </div>

        <form 
            id = "formulario-cliente"
            wire:key="form-cliente-{{ $clienteEditandoId ?? 'nuevo' }}"
            wire:submit="guardar"
            class="space-y-6 p-6">

            <div class="grid gap-5 md:grid-cols-2
                        xl:grid-cols-3">

                <!-- CÓDIGO -->
                <div>
                    <label for="codigo"
                           class="mb-2 block text-sm font-medium">
                        Código del cliente *
                    </label>

                    <input id="codigo"
                           type="text"
                           wire:model="codigo"
                           placeholder="CLI001"
                           required
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('codigo')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- NOMBRE -->
                <div>
                    <label for="nombre"
                           class="mb-2 block text-sm font-medium">
                        Nombre completo *
                    </label>

                    <input id="nombre"
                           type="text"
                           wire:model="nombre"
                           placeholder="Nombre y apellidos"
                           required
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('nombre')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- DOCUMENTO -->
                <div>
                    <label for="documento"
                           class="mb-2 block text-sm font-medium">
                        DNI / Documento
                    </label>

                    <input id="documento"
                           type="text"
                           wire:model="documento"
                           placeholder="Documento de identidad"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('documento')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- TELÉFONO -->
                <div>
                    <label for="telefono"
                           class="mb-2 block text-sm font-medium">
                        Celular
                    </label>

                    <input id="telefono"
                           type="tel"
                           wire:model="telefono"
                           placeholder="Número de contacto"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('telefono')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- SECTOR -->
                <div>
                    <label for="sector"
                           class="mb-2 block text-sm font-medium">
                        Sector / Comunidad
                    </label>

                    <input id="sector"
                           type="text"
                           wire:model="sector"
                           placeholder="Ej. Centro, Upiña..."
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('sector')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- DIRECCIÓN -->
                <div>
                    <label for="direccion"
                           class="mb-2 block text-sm font-medium">
                        Dirección
                    </label>

                    <input id="direccion"
                           type="text"
                           wire:model="direccion"
                           placeholder="Dirección del cliente"
                           class="w-full rounded-xl border
                                  border-zinc-300 bg-transparent
                                  px-4 py-3 text-sm
                                  focus:border-blue-500
                                  focus:outline-none
                                  dark:border-zinc-700">

                    @error('direccion')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- OBSERVACIONES -->
                <div class="md:col-span-2 xl:col-span-3">

                    <label for="observaciones"
                           class="mb-2 block text-sm font-medium">
                        Observaciones
                    </label>

                    <textarea id="observaciones"
                              rows="3"
                              wire:model="observaciones"
                              placeholder="Notas adicionales..."
                              class="w-full rounded-xl border
                                     border-zinc-300 bg-transparent
                                     px-4 py-3 text-sm
                                     focus:border-blue-500
                                     focus:outline-none
                                     dark:border-zinc-700"></textarea>

                    @error('observaciones')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <!-- BOTÓN GUARDAR -->

            <!-- BOTONES DEL FORMULARIO -->
            <div class="flex flex-wrap justify-end gap-3
                        border-t border-zinc-200 pt-5
                        dark:border-zinc-800">

                @if ($clienteEditandoId !== null)

                    <button
                        type="button"
                        wire:click="cancelarEdicion"
                        class="rounded-xl border border-zinc-300
                            px-6 py-3 text-sm font-semibold
                            text-zinc-700 transition
                            hover:bg-zinc-100
                            dark:border-zinc-700
                            dark:text-zinc-200
                            dark:hover:bg-zinc-800">

                        Cancelar edición

                    </button>

                @endif

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="guardar"
                    class="rounded-xl bg-blue-600
                        px-7 py-3 text-sm font-bold
                        text-white shadow-lg
                        shadow-blue-600/20
                        transition hover:bg-blue-700
                        disabled:opacity-50">

                    <span wire:loading.remove wire:target="guardar">
                        {{ $clienteEditandoId !== null
                            ? 'Guardar cambios'
                            : 'Guardar cliente' }}
                    </span>

                    <span wire:loading wire:target="guardar">
                        Guardando...
                    </span>

                </button>

            </div>

        </form>
    </div>

    <!-- LISTADO -->
    <div class="overflow-hidden rounded-2xl border
                border-zinc-200 bg-white shadow-sm
                dark:border-zinc-800 dark:bg-zinc-900">

        <div class="flex flex-col gap-4 p-6
                    sm:flex-row sm:items-center
                    sm:justify-between">

            <div>
                <h2 class="text-xl font-bold">
                    Clientes registrados
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Consulta los clientes guardados.
                </p>
            </div>

            <input type="search"
                   wire:model.live.debounce.350ms="buscar"
                   placeholder="Buscar cliente..."
                   class="w-full rounded-xl border
                          border-zinc-300 bg-transparent
                          px-4 py-3 text-sm
                          focus:border-blue-500
                          focus:outline-none
                          sm:max-w-xs dark:border-zinc-700">

        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">

                <thead class="bg-zinc-100 text-zinc-600
                              dark:bg-zinc-800
                              dark:text-zinc-300">

                    <tr>
                        <th class="px-5 py-4">Código</th>
                        <th class="px-5 py-4">Cliente</th>
                        <th class="px-5 py-4">Documento</th>
                        <th class="px-5 py-4">Celular</th>
                        <th class="px-5 py-4">Sector</th>
                        <th class="px-5 py-4">Acciones</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-zinc-200
                              dark:divide-zinc-800">

                    @forelse ($clientes as $cliente)

                        <tr wire:key="cliente-{{ $cliente->id }}"
                            class="transition
                                   hover:bg-zinc-50
                                   dark:hover:bg-zinc-800/60">

                            <td class="px-5 py-4">
                                <span class="rounded-lg
                                             bg-blue-100 px-3 py-1
                                             font-bold text-blue-700
                                             dark:bg-blue-950
                                             dark:text-blue-300">
                                    {{ $cliente->codigo }}
                                </span>
                            </td>

                            <td class="px-5 py-4 font-semibold">
                                {{ $cliente->nombre }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $cliente->documento ?: '-' }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $cliente->telefono ?: '-' }}
                            </td>

                            <td class="px-5 py-4">
                                {{ $cliente->sector ?: '-' }}
                            </td>
                            
                            <td class="px-5 py-4">

                                <button
                                    type="button"
                                    wire:click="editar({{ $cliente->id }})"
                                    x-on:click="document.getElementById('formulario-cliente').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                    class="inline-flex items-center gap-2
                                        rounded-lg bg-blue-100
                                        px-4 py-2 text-xs font-bold
                                        text-blue-700 transition
                                        hover:bg-blue-200
                                        dark:bg-blue-950
                                        dark:text-blue-300
                                        dark:hover:bg-blue-900">

                                    Editar

                                </button>
                                
                                
                                <a href="{{ route('clientes.show', $cliente->id) }}"
                                wire:navigate
                                class="ml-2 inline-flex items-center
                                        rounded-lg bg-emerald-100
                                        px-4 py-2 text-xs font-bold
                                        text-emerald-700
                                        transition hover:bg-emerald-200
                                        dark:bg-emerald-950
                                        dark:text-emerald-300">

                                    Ver ficha

                                </a>


                            </td>


                        </tr>

                    @empty

                        <tr>
                            <td colspan="6"
                                class="px-5 py-12 text-center
                                       text-zinc-500">

                                Todavía no hay clientes
                                para mostrar.

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <div class="border-t border-zinc-200 p-5
                    dark:border-zinc-800">

            {{ $clientes->links() }}

        </div>

    </div>

</div>
