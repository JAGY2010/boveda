<x-layouts::app :title="$negocio->nombre">
    <div class="mx-auto w-full max-w-4xl">
        <a href="{{ route('admin.locales.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Locales</a>
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $negocio->nombre }}</h1>
                <p class="text-sm text-zinc-500">{{ $negocio->ciudad }} · {{ $usuarios->count() }} usuario(s)</p>
            </div>
            <a href="{{ route('admin.usuarios.create', ['local' => $negocio->id]) }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo usuario
            </a>
        </div>

        {{-- Suscripción --}}
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-zinc-400">Suscripción</p>
                    <div class="mt-1"><x-suscripcion-badge :negocio="$negocio" /></div>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <form method="POST" action="{{ route('admin.locales.renovar', $negocio) }}" class="flex items-end gap-2">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-zinc-500">Meses</label>
                            <select name="meses" class="rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ $m }} {{ $m === 1 ? 'mes' : 'meses' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Renovar</button>
                    </form>

                    @if ($negocio->suspendido)
                        <form method="POST" action="{{ route('admin.locales.reactivar', $negocio) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-950/40">Reactivar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.locales.suspender', $negocio) }}"
                              onsubmit="return confirm('¿Suspender el acceso de {{ $negocio->nombre }}? Los usuarios verán la pantalla de local cerrado hasta que lo reactives.')">
                            @csrf
                            <button type="submit" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40">Suspender</button>
                        </form>
                    @endif
                </div>
            </div>
            <p class="mt-3 text-xs text-zinc-400">Al renovar se suman los meses a la fecha vigente (si aún no vence) o desde hoy (si ya venció). Máximo recomendado: 12 meses.</p>
        </div>

        {{-- Usuarios del local --}}
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Correo</th>
                        <th class="px-4 py-3 font-semibold">Rol</th>
                        <th class="px-4 py-3 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($usuarios as $u)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-zinc-500/15 px-2.5 py-0.5 text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ $u->rolLabel() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-reset-password :usuario="$u" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-400">Este local aún no tiene usuarios. Crea el dueño o un empleado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
