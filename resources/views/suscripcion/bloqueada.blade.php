<x-layouts::app :title="'Suscripción'">
    <div class="mx-auto flex min-h-[70vh] w-full max-w-lg flex-col items-center justify-center text-center">
        @php
            $suspendida = $negocio->estadoSuscripcion() === 'suspendida';
            $esDueno = auth()->user()->isOwner();
        @endphp

        <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/15 text-3xl">
            {{ $suspendida ? '⏸️' : '🔒' }}
        </div>

        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            {{ $suspendida ? 'Local suspendido' : 'Suscripción vencida' }}
        </h1>

        <div class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
            @if ($esDueno)
                @if ($suspendida)
                    <p>El administrador suspendió temporalmente el acceso a <b>{{ $negocio->nombre }}</b>. Comunícate con él para reactivarlo.</p>
                @else
                    <p>El acceso a <b>{{ $negocio->nombre }}</b> está en pausa porque la suscripción venció el
                        <b>{{ optional($negocio->suscripcion_hasta)->format('d/m/Y') }}</b>. Ponte al día con el pago y avísale al administrador para reactivarlo.</p>
                @endif
                <p class="mt-3 text-emerald-700 dark:text-emerald-400">Tu información está intacta: no se borra nada.</p>
            @else
                <p><b>{{ $negocio->nombre }}</b> está temporalmente cerrado. Comunícate con el dueño del local para más información.</p>
                <p class="mt-3 text-emerald-700 dark:text-emerald-400">La información del negocio está segura.</p>
            @endif
        </div>

        <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
            @if (auth()->user()->hasMultipleLocales())
                <form method="POST" action="{{ route('local.cambiar') }}" class="flex items-center gap-2">
                    @csrf
                    <select name="local_id" onchange="this.form.submit()"
                            class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @foreach ($localesAccesibles as $loc)
                            <option value="{{ $loc->id }}" @selected($negocio->id === $loc->id)>{{ $loc->nombre }}</option>
                        @endforeach
                    </select>
                    <span class="text-xs text-zinc-400">cambiar de local</span>
                </form>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Cerrar sesión</button>
            </form>
        </div>
    </div>
</x-layouts::app>
