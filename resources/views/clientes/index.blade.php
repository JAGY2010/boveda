<x-layouts::app :title="'Clientes'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Clientes</h1>
                <p class="text-sm text-zinc-500">{{ $clientes->count() }} {{ $clientes->count() == 1 ? 'resultado' : 'registrados' }}</p>
            </div>
            <a href="{{ route('clientes.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo cliente
            </a>
        </div>

        <form method="GET" class="mb-4 max-w-md">
            <input name="q" value="{{ $q }}" placeholder="Buscar por nombre o cédula…" autocomplete="off"
                   class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Cédula</th>
                        <th class="px-4 py-3 font-semibold">Celular</th>
                        <th class="px-4 py-3 font-semibold">Dirección</th>
                        <th class="px-4 py-3 text-right font-semibold">Empeños</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($clientes as $c)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $c->nombre }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $c->cedula }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $c->tel }}</td>
                            <td class="px-4 py-3 text-zinc-400">{{ $c->direccion }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $c->empenos_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-400">{{ $q ? 'Sin resultados' : 'Aún no hay clientes' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
