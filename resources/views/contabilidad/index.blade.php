<x-layouts::app :title="$verDinero ? 'Contabilidad' : 'Caja y gastos'">
    <div class="mx-auto w-full max-w-6xl">
        <x-flash />

        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $verDinero ? 'Contabilidad' : 'Caja y gastos' }}</h1>
            <p class="text-sm text-zinc-500">{{ $verDinero ? 'El dinero del negocio, en tiempo real' : 'El efectivo que debe haber en la caja y registrar gastos' }}</p>
        </div>

        @if ($verDinero)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-kpi label="Caja disponible" :value="cop($negocio->caja)" sub="efectivo" accent="emerald" />
                <x-kpi label="Prestado" :value="cop($negocio->prestado)" sub="en empeños" accent="amber" />
                <x-kpi label="Inventario" :value="cop($negocio->inventario_valor)" sub="para vender" accent="zinc" />
                <x-kpi label="Total invertido" :value="cop($negocio->totalInvertido())" sub="capital de trabajo" accent="sky" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-kpi label="Ganancia bruta" :value="cop($negocio->gananciaBruta())" :sub="'interés ' . cop($negocio->acum_interes) . ' + ventas ' . cop($negocio->acum_margen)" accent="sky" />
                <x-kpi label="Gastos" :value="cop($negocio->acum_gastos)" sub="acumulados" accent="zinc" />
                <x-kpi label="Ganancia neta" :value="cop($negocio->gananciaNeta())" sub="bruta − gastos" accent="emerald" />
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                <x-kpi label="Caja disponible" :value="cop($negocio->caja)" sub="efectivo que debe haber" accent="emerald" />
                <div class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                    <span class="font-bold text-amber-600">•</span>
                    <span>Este es el efectivo que debe haber en la caja del local. Puedes registrar los gastos abajo.</span>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-5 lg:grid-cols-3">
            @if ($verDinero)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Movimientos recientes</p>
                    <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($movimientos as $m)
                            <li class="flex justify-between gap-3 py-2">
                                <span class="text-zinc-500">{{ $m->fecha->format('d/m/Y') }} · {{ $m->descripcion }}</span>
                                <span class="font-semibold tabular-nums {{ $m->monto >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ cop($m->monto) }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-zinc-400">Sin movimientos</li>
                        @endforelse
                    </ul>
                </div>
            @endif

            <div class="space-y-4 {{ $verDinero ? '' : 'lg:col-span-3' }}">
                @if ($verDinero)
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Capital</p>
                        <form method="POST" action="{{ route('contabilidad.capital') }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="text" inputmode="numeric" name="monto" required placeholder="500.000" class="money w-32 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            <button name="tipo" value="agregar" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">+ Agregar</button>
                            <button name="tipo" value="retirar" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">− Retirar</button>
                        </form>
                    </div>
                @endif

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Registrar gasto</p>
                    <form method="POST" action="{{ route('contabilidad.gasto') }}" class="space-y-2">
                        @csrf
                        <input name="categoria" required placeholder="Arriendo, nómina, servicios…" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <div class="flex gap-2">
                            <input type="text" inputmode="numeric" name="monto" required placeholder="200.000" class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Guardar</button>
                        </div>
                    </form>
                    <ul class="mt-3 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($gastos as $g)
                            <li class="flex justify-between py-1.5"><span class="text-zinc-500">{{ $g->categoria }}{{ $g->descripcion ? ' · ' . $g->descripcion : '' }}</span><span class="font-semibold text-red-600 tabular-nums">{{ cop(-$g->monto) }}</span></li>
                        @empty
                            <li class="py-1.5 text-zinc-400">Sin gastos</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
