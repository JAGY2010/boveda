<x-layouts::app :title="'Locales'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Locales</h1>
                <p class="text-sm text-zinc-500">{{ $locales->count() }} negocios usando Bóveda</p>
            </div>
            <a href="{{ route('admin.locales.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo local
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Local</th>
                        <th class="px-4 py-3 font-semibold">NIT</th>
                        <th class="px-4 py-3 text-right font-semibold">Clientes</th>
                        <th class="px-4 py-3 text-right font-semibold">Empeños</th>
                        <th class="px-4 py-3 text-right font-semibold">Caja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($locales as $l)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $l->nombre }}</div>
                                <div class="text-xs text-zinc-400">{{ $l->ciudad }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $l->nit }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $l->clientes_count }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $l->empenos_count }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($l->caja) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
