<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @isset($localActual)
                <div class="px-3 pb-2">
                    @if (auth()->user()->hasMultipleLocales())
                        <form method="POST" action="{{ route('local.cambiar') }}">
                            @csrf
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-zinc-400">Local activo</label>
                            <select name="local_id" onchange="this.form.submit()"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @foreach ($localesAccesibles as $loc)
                                    <option value="{{ $loc->id }}" @selected($localActual && $localActual->id === $loc->id)>{{ $loc->nombre }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Local</div>
                        <div class="truncate text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $localActual->nombre }}</div>
                    @endif
                </div>

                <flux:sidebar.nav>
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Tablero') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('clientes.index')" :current="request()->routeIs('clientes.*')" wire:navigate>
                        {{ __('Clientes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('empenos.index')" :current="request()->routeIs('empenos.*')" wire:navigate>
                        {{ __('Empeños') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-bag" :href="route('inventario.index')" :current="request()->routeIs('inventario.*')" wire:navigate>
                        {{ __('Ventas e inventario') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-pie" :href="route('contabilidad.index')" :current="request()->routeIs('contabilidad.*')" wire:navigate>
                        {{ auth()->user()->puedeVerDinero() ? __('Contabilidad') : __('Caja y gastos') }}
                    </flux:sidebar.item>
                    @if (auth()->user()->puedeVerDinero())
                        <flux:sidebar.item icon="document-chart-bar" :href="route('reporte.index')" :current="request()->routeIs('reporte.*')" wire:navigate>
                            {{ __('Reporte') }}
                        </flux:sidebar.item>
                    @endif
                    @if (auth()->user()->hasMultipleLocales())
                        <flux:sidebar.item icon="chart-bar" :href="route('consolidado')" :current="request()->routeIs('consolidado')" wire:navigate>
                            {{ __('Consolidado') }}
                        </flux:sidebar.item>
                    @endif
                    @if (auth()->user()->puedeEditar())
                        <flux:sidebar.item icon="user-plus" :href="route('equipo.index')" :current="request()->routeIs('equipo.*')" wire:navigate>
                            {{ __('Empleados') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="trash" :href="route('eliminaciones.index')" :current="request()->routeIs('eliminaciones.*')" wire:navigate>
                            {{ __('Historial (eliminados)') }}
                        </flux:sidebar.item>
                    @endif
                    @if (auth()->user()->puedeEditar())
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('config.edit')" :current="request()->routeIs('config.*')" wire:navigate>
                            {{ __('Configuración') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.nav>

                @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('local.salir') }}" class="px-3 pt-1">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-2 py-1.5 text-left text-sm text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">← Salir del local</button>
                    </form>
                @endif
            @endisset

            @if (auth()->user()->isAdmin())
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Administración')" class="grid">
                        <flux:sidebar.item icon="building-storefront" :href="route('admin.locales.index')" :current="request()->routeIs('admin.locales.*')" wire:navigate>
                            {{ __('Locales') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user-group" :href="route('admin.usuarios.index')" :current="request()->routeIs('admin.usuarios.*')" wire:navigate>
                            {{ __('Usuarios') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endif

            <flux:spacer />

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

        @if (! empty($localActual) && $localActual->suscripcionPorVencer())
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                @php($d = $localActual->diasParaVencer())
                Tu suscripción vence {{ $d === 0 ? 'hoy' : ($d === 1 ? 'mañana' : 'en '.$d.' días') }}
                ({{ $localActual->suscripcion_hasta->format('d/m/Y') }}). Renueva con el administrador para no perder el acceso.
            </div>
        @endif

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <script src="{{ asset('js/money.js') }}"></script>
        @fluxScripts
    </body>
</html>
