<x-layouts::app :title="'Empleados'">
    <div class="mx-auto w-full max-w-4xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Empleados</h1>
                <p class="text-sm text-zinc-500">Equipo de {{ $local->nombre }}</p>
            </div>
            <a href="{{ route('equipo.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo empleado
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[420px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Correo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($empleados as $e)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $e->name }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $e->email }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-10 text-center text-zinc-400">Aún no tienes empleados. Crea el primero.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs text-zinc-400">El empleado solo verá este local; puede registrar clientes, empeños, pagos y ventas, pero no el dinero ni las ganancias.</p>
    </div>
</x-layouts::app>
