<x-layouts::app :title="'Ventas e inventario'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Ventas e inventario</h1>
            <p class="text-sm text-zinc-500">{{ $disponibles->count() }} para vender · {{ $vendidos->count() }} vendidos</p>
        </div>

        <form method="POST" action="{{ route('inventario.comprar') }}"
              class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <p class="w-full text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Compra directa (el cliente vende)</p>
            <div class="flex-1">
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Artículo</label>
                <input name="descripcion" required placeholder="Ej: Bicicleta Trek" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Valor de compra</label>
                <input type="text" inputmode="numeric" name="costo" required placeholder="300.000" class="money w-36 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Fecha de compra</label>
                <input type="date" name="fecha_compra" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Comprar</button>
        </form>

        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-zinc-400">Para vender</p>
        <div class="mb-6 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[620px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Artículo</th>
                        <th class="px-4 py-3 font-semibold">Comprado</th>
                        <th class="px-4 py-3 text-right font-semibold">Costo</th>
                        <th class="px-4 py-3 text-right font-semibold">Vender</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($disponibles as $it)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $it->descripcion }}</div>
                                <div class="text-xs text-zinc-400">{{ $it->origen === 'perdido' ? 'No retirado · empeño #' . optional($it->empeno)->numero : 'Compra directa' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ optional($it->fecha_compra)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($it->costo) }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('inventario.vender', $it) }}" class="flex flex-wrap items-center justify-end gap-2">
                                    @csrf
                                    <input type="text" inputmode="numeric" name="valor" required placeholder="{{ number_format(round($it->costo * 1.4), 0, ',', '.') }}" class="money w-28 rounded-lg border border-zinc-300 bg-zinc-50 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                    <input type="date" name="fecha_venta" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="rounded-lg border border-zinc-300 bg-zinc-50 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500">Vender</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-zinc-400">Nada por vender. Los artículos no retirados o comprados directo aparecen aquí.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-zinc-400">Vendidos</p>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[620px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Artículo</th>
                        <th class="px-4 py-3 font-semibold">Comprado</th>
                        <th class="px-4 py-3 font-semibold">Vendido</th>
                        <th class="px-4 py-3 text-right font-semibold">Costo</th>
                        <th class="px-4 py-3 text-right font-semibold">Venta</th>
                        <th class="px-4 py-3 text-right font-semibold">Ganancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($vendidos as $it)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">{{ $it->descripcion }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ optional($it->fecha_compra)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ optional($it->fecha_venta)->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($it->costo) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($it->venta) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $it->venta - $it->costo >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ cop($it->venta - $it->costo) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-400">Aún no hay ventas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
