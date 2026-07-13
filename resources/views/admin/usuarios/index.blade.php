<x-layouts::app :title="'Usuarios'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Usuarios</h1>
                <p class="text-sm text-zinc-500">{{ $usuarios->total() }} perfiles · directorio global</p>
            </div>
            <a href="{{ route('admin.usuarios.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo usuario
            </a>
        </div>

        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
            <div class="min-w-[180px] flex-1">
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Buscar</label>
                <input name="q" value="{{ $q }}" placeholder="Nombre o correo…"
                       class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Rol</label>
                <select name="role" class="rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="">Todos</option>
                    <option value="owner" @selected($role === 'owner')>Dueño</option>
                    <option value="employee" @selected($role === 'employee')>Empleado</option>
                    <option value="admin" @selected($role === 'admin')>Admin</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Local</label>
                <select name="local" class="rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="">Todos</option>
                    @foreach ($locales as $l)
                        <option value="{{ $l->id }}" @selected((string) $localId === (string) $l->id)>{{ $l->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white">Filtrar</button>
            @if ($q !== '' || $role || $localId)
                <a href="{{ route('admin.usuarios.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Limpiar</a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Correo</th>
                        <th class="px-4 py-3 font-semibold">Rol</th>
                        <th class="px-4 py-3 font-semibold">Locales</th>
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
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $u->isAdmin() ? 'Todos' : ($u->negocios->pluck('nombre')->join(', ') ?: '—') }}
                            </td>
                            <td class="px-4 py-3">
                                @unless ($u->isAdmin())
                                    <x-reset-password :usuario="$u" />
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-400">No hay usuarios que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $usuarios->links() }}
        </div>
    </div>
</x-layouts::app>
