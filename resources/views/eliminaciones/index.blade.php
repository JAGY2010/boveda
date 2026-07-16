<x-layouts::app :title="'Historial de eliminados'">
    <div class="mx-auto w-full max-w-4xl">
        <x-flash />

        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Historial de empeños eliminados</h1>
            <p class="text-sm text-zinc-500">{{ $eliminaciones->total() }} registro(s) · solo lo ve el dueño</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Eliminado</th>
                        <th class="px-4 py-3 font-semibold">Empeño</th>
                        <th class="px-4 py-3 font-semibold">Cliente</th>
                        <th class="px-4 py-3 font-semibold">Artículo</th>
                        <th class="px-4 py-3 text-right font-semibold">Valor</th>
                        <th class="px-4 py-3 font-semibold">Motivo</th>
                        <th class="px-4 py-3 font-semibold">Por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($eliminaciones as $e)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ $e->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">#{{ $e->numero }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $e->cliente_nombre }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $e->articulo }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($e->principal) }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $e->motivo ?: '—' }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $e->user_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-400">No hay empeños eliminados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $eliminaciones->links() }}</div>
    </div>
</x-layouts::app>
