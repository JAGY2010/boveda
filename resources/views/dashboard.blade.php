<x-layouts::app :title="'Tablero'">
    <div class="mx-auto w-full max-w-6xl">
        <x-flash />

        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">{{ $negocio->ciudad }}</p>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $negocio->nombre }}</h1>
                <p class="text-sm text-zinc-500">Hoy es {{ now()->isoFormat('D [de] MMMM [de] YYYY') }} · {{ $negocio->clientes()->count() }} clientes</p>
            </div>
            <a href="{{ route('empenos.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                + Nuevo empeño
            </a>
        </div>

        @if (auth()->user()->puedeVerDinero())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-kpi label="Caja disponible" :value="cop($negocio->caja)" sub="efectivo en el negocio" accent="emerald" />
                <x-kpi label="Prestado" :value="cop($negocio->prestado)" :sub="$activos->count() . ' empeños activos'" accent="amber" />
                <x-kpi label="Inventario" :value="cop($negocio->inventario_valor)" sub="artículos para vender" accent="zinc" />
                <x-kpi label="Ganancia neta" :value="cop($negocio->gananciaNeta())" :sub="'bruta ' . cop($negocio->gananciaBruta())" accent="sky" />
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-kpi label="Caja disponible" :value="cop($negocio->caja)" sub="efectivo que debe haber" accent="emerald" />
                <x-kpi label="Clientes" :value="$negocio->clientes()->count()" sub="registrados" accent="zinc" />
                <x-kpi label="Empeños activos" :value="$activos->count()" sub="en curso" accent="amber" />
                <x-kpi label="Vencen esta semana" :value="$venceSemana" sub="para avisar" accent="sky" />
            </div>
            <div class="mt-3 flex items-start gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                <span class="font-bold text-amber-600">•</span>
                <span>Como <b>empleado</b> ves la <b>caja</b> (para saber cuánto efectivo debe haber) y puedes registrar clientes, empeños, pagos, ventas y gastos. No ves las ganancias ni el capital invertido.</span>
            </div>
        @endif

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <a href="{{ route('empenos.index', ['estado' => 'activos']) }}" wire:navigate class="rounded-xl border border-sky-500/40 bg-sky-500/10 px-4 py-3 transition hover:bg-sky-500/20">
                <div class="text-2xl font-semibold tabular-nums text-sky-700 dark:text-sky-300">{{ $venceSemana }}</div>
                <div class="text-xs font-semibold text-sky-700 dark:text-sky-300">Vencen esta semana · avisar</div>
            </a>
            <a href="{{ route('empenos.index', ['estado' => 'mora']) }}" wire:navigate class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 transition hover:bg-amber-500/20">
                <div class="text-2xl font-semibold tabular-nums text-amber-700 dark:text-amber-400">{{ $enMora }}</div>
                <div class="text-xs font-semibold text-amber-700 dark:text-amber-400">En mora</div>
            </a>
            <a href="{{ route('empenos.index', ['estado' => 'perder']) }}" wire:navigate class="rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 transition hover:bg-red-500/20">
                <div class="text-2xl font-semibold tabular-nums text-red-700 dark:text-red-300">{{ $porPerder->count() }}</div>
                <div class="text-xs font-semibold text-red-700 dark:text-red-300">Por perder · dispones del artículo</div>
            </a>
        </div>

        <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Próximos vencimientos</p>
            @if ($proximos->count())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-zinc-400">
                                <th class="py-2 pr-4 font-semibold">Cliente / artículo</th>
                                <th class="py-2 pr-4 font-semibold">Estado</th>
                                <th class="py-2 pr-4 text-right font-semibold">Debe hoy</th>
                                <th class="py-2 font-semibold">Vence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($proximos as $e)
                                @php $dv = now()->startOfDay()->diffInDays($e->vencimiento(), false); @endphp
                                <tr class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                                    onclick="window.location='{{ route('empenos.show', $e) }}'">
                                    <td class="py-3 pr-4">
                                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $e->cliente->nombre }}</div>
                                        <div class="text-xs text-zinc-400">{{ $e->articulo }}</div>
                                    </td>
                                    <td class="py-3 pr-4"><x-estado :estado="$e->estadoCalculado()" /></td>
                                    <td class="py-3 pr-4 text-right tabular-nums">{{ cop($e->deudaHoy()) }}</td>
                                    <td class="py-3">
                                        {{ $e->vencimiento()->format('d/m/Y') }}
                                        <div class="text-xs text-zinc-400">{{ $dv < 0 ? 'vencido' : 'en ' . (int) $dv . ' días' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center text-zinc-400">
                    <p class="text-lg text-zinc-500">Sin empeños activos</p>
                    <p class="text-sm">Crea el primero con “Nuevo empeño”.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
