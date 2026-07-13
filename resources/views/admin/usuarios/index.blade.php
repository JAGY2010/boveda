<x-layouts::app :title="'Usuarios'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Usuarios</h1>
                <p class="text-sm text-zinc-500">{{ $usuarios->count() }} perfiles</p>
            </div>
            <a href="{{ route('admin.usuarios.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo usuario
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Correo</th>
                        <th class="px-4 py-3 font-semibold">Rol</th>
                        <th class="px-4 py-3 font-semibold">Locales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($usuarios as $u)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-zinc-500/15 px-2.5 py-0.5 text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ $u->rolLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $u->isAdmin() ? 'Todos' : ($u->negocios->pluck('nombre')->join(', ') ?: '—') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
