<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-zinc-200 bg-slate-50
                dark:border-slate-800 dark:bg-slate-950">
            <flux:sidebar.header>

                <a href="{{ route('dashboard') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-xl px-2 py-3">

                    <div class="flex size-11 shrink-0 items-center justify-center
                                rounded-xl bg-blue-600 text-white shadow-lg
                                shadow-blue-900/30">

                        <flux:icon.wifi class="size-6" />

                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-base font-extrabold
                                tracking-wide text-zinc-900 dark:text-white">
                            ULTRA
                        </p>
                    </div>

                </a>

                <flux:sidebar.collapse class="lg:hidden" />

            </flux:sidebar.header>



            {{-- Navegación agrupada por la tarea que realiza el operador. --}}
            {{-- Únicamente cambia el menú: no altera cargos, pagos ni servicios. --}}
            <flux:sidebar.nav class="mt-4 space-y-4">

                <flux:sidebar.group heading="INICIO" class="grid">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        class="rounded-xl"
                        wire:navigate>
                        Panel de control
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="CLIENTES Y RED" class="grid">
                    @if(\App\Support\IspAccess::allows(auth()->user(), 'clientes.view'))
<flux:sidebar.item
                        icon="users"
                        :href="route('clientes.index')"
                        :current="request()->routeIs('clientes.*')"
                        wire:navigate>
                        Clientes
                    </flux:sidebar.item>
                    @endif

                    @if(\App\Support\IspAccess::allows(auth()->user(), 'servicios.view'))
<flux:sidebar.item
                        icon="wifi"
                        :href="route('servicios.index')"
                        :current="request()->routeIs('servicios.*')"
                        wire:navigate>
                        Servicios de Internet
                    </flux:sidebar.item>
                    @endif

                    @if(\App\Support\IspAccess::allows(auth()->user(), 'nap.view'))
<flux:sidebar.item
                        icon="server-stack"
                        :href="route('nap.index')"
                        :current="request()->routeIs('nap.*')"
                        wire:navigate>
                        Cajas NAP
                    </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                @if(\App\Support\IspAccess::allows(auth()->user(), 'cobranza.view'))

                <flux:sidebar.group heading="FACTURACIÓN Y COBROS" class="grid">
                    <flux:sidebar.item
                        icon="banknotes"
                        :href="route('cobranza.index')"
                        :current="request()->routeIs('cobranza.index', 'cobranza.ficha', 'cobranza.adelantos', 'cobranza.distribuir-pago', 'cobranza.mensualidades')">
                        Cobros y deudas
                    </flux:sidebar.item>

                </flux:sidebar.group>
                @endif
                @if(\App\Support\IspAccess::allows(auth()->user(), 'finanzas.cobros'))

                <flux:sidebar.group heading="ADMINISTRACIÓN" class="grid">
                    <flux:sidebar.item
                        icon="chart-bar"
                        :href="route('finanzas.index')"
                        :current="request()->routeIs('finanzas.*')">
                        Finanzas
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif

                @if(\App\Support\IspAccess::allows(auth()->user(), 'usuarios.manage'))
                <flux:sidebar.group heading="SEGURIDAD" class="grid">
                    <flux:sidebar.item icon="shield-check" :href="route('usuarios.index')" :current="request()->routeIs('usuarios.*')" wire:navigate>
                        Usuarios y permisos
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>


            <flux:spacer />


            <flux:sidebar.nav>

                <flux:sidebar.item
                    icon="cog-6-tooth"
                    :href="route('profile.edit')"
                    wire:navigate>
                    Mi configuración
                </flux:sidebar.item>

            </flux:sidebar.nav>


            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
