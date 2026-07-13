<x-layouts::app :title="'Empeños'">
    <div class="mx-auto w-full max-w-6xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Empeños</h1>
                <p class="text-sm text-zinc-500">{{ $empenos->where('estado', 'activo')->count() }} activos · {{ $empenos->count() }} en la lista</p>
            </div>
            <a href="{{ route('empenos.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo empeño
            </a>
        </div>

        <form method="GET" class="mb-4 max-w-md">
            <input name="q" value="{{ $q }}" placeholder="Buscar por nombre, cédula o artículo…" autocomplete="off"
                   class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[600px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">#</th>
                        <th class="px-4 py-3 font-semibold">Cliente / artículo</th>
                        <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Vence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($empenos as $e)
                        <tr class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                            onclick="window.location='{{ route('empenos.show', $e) }}'">
                            <td class="px-4 py-3 font-semibold text-zinc-400">#{{ $e->numero }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $e->cliente->nombre }}</div>
                                <div class="text-xs text-zinc-400">{{ $e->articulo }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($e->saldo) }}</td>
                            <td class="px-4 py-3"><x-estado :estado="$e->estadoCalculado()" /></td>
                            <td class="px-4 py-3">{{ $e->estado === 'activo' ? $e->vencimiento()->format('d/m/Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-400">{{ $q ? 'Sin resultados' : 'Sin empeños' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
