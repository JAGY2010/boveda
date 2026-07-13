<x-layouts::app :title="'Consolidado'">
    <div class="mx-auto w-full max-w-6xl">
        <x-flash />

        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Consolidado</h1>
            <p class="text-sm text-zinc-500">Tus {{ $locales->count() }} locales, juntos y por separado</p>
        </div>

        @php
            $tCaja = $locales->sum('caja');
            $tPrestado = $locales->sum('prestado');
            $tInv = $locales->sum('inventario_valor');
            $tNeta = $locales->sum(fn ($l) => $l->gananciaNeta());
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-kpi label="Caja (total)" :value="cop($tCaja)" sub="todos los locales" accent="emerald" />
            <x-kpi label="Prestado (total)" :value="cop($tPrestado)" sub="en empeños" accent="amber" />
            <x-kpi label="Inventario (total)" :value="cop($tInv)" sub="para vender" accent="zinc" />
            <x-kpi label="Ganancia neta (total)" :value="cop($tNeta)" sub="suma de locales" accent="sky" />
        </div>

        <div class="mt-6 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Local</th>
                        <th class="px-4 py-3 text-right font-semibold">Caja</th>
                        <th class="px-4 py-3 text-right font-semibold">Prestado</th>
                        <th class="px-4 py-3 text-right font-semibold">Inventario</th>
                        <th class="px-4 py-3 text-right font-semibold">Ganancia neta</th>
                        <th class="px-4 py-3 text-right font-semibold">Total invertido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($locales as $l)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $l->nombre }}</div>
                                <div class="text-xs text-zinc-400">{{ $l->ciudad }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($l->caja) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($l->prestado) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($l->inventario_valor) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ cop($l->gananciaNeta()) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ cop($l->totalInvertido()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-zinc-200 font-semibold dark:border-zinc-700">
                        <td class="px-4 py-3">Total</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ cop($tCaja) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ cop($tPrestado) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ cop($tInv) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ cop($tNeta) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ cop($locales->sum(fn ($l) => $l->totalInvertido())) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="mt-3 text-xs text-zinc-400">Para operar un local en particular (empeños, pagos, ventas), cámbialo en el selector “Local activo” arriba a la izquierda.</p>
    </div>
</x-layouts::app>
