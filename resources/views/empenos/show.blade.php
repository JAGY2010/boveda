<x-layouts::app :title="'Empeño #' . $empeno->numero">
    <div class="mx-auto w-full max-w-5xl">
        <a href="{{ route('empenos.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Empeños</a>
        <x-flash />

        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Empeño #{{ $empeno->numero }}</p>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $empeno->cliente->nombre }}</h1>
                <p class="text-sm text-zinc-500">{{ $empeno->articulo }}</p>
            </div>
            <x-estado :estado="$empeno->estadoCalculado()" />
        </div>

        @php $activo = $empeno->estado === 'activo'; @endphp

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-kpi label="Saldo capital" :value="cop($empeno->saldo)" :sub="'prestado ' . cop($empeno->principal)" accent="amber" />
                    <x-kpi label="Vence" :value="$activo ? $empeno->vencimiento()->format('d/m/Y') : '—'" :sub="$activo ? 'interés ' . rtrim(rtrim($empeno->pct, '0'), '.') . '%/mes' : 'cerrado'" accent="emerald" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-kpi label="Cuotas pagadas" :value="$empeno->meses_pagados" sub="de interés" accent="zinc" />
                    <x-kpi label="Meses sin pagar" :value="$activo ? $empeno->mesesSinPagar() : '—'" :sub="'límite: ' . $empeno->plazo" :accent="$activo && $empeno->mesesSinPagar() >= $empeno->plazo ? 'amber' : 'sky'" />
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Datos</p>
                    <dl class="grid grid-cols-1 gap-y-2 text-sm sm:grid-cols-2">
                        <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800"><dt class="text-zinc-400">Cédula</dt><dd>{{ $empeno->cliente->cedula }}</dd></div>
                        <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800"><dt class="text-zinc-400">Celular</dt><dd>{{ $empeno->cliente->tel }}</dd></div>
                        <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800"><dt class="text-zinc-400">Serial / IMEI</dt><dd>{{ $empeno->serial ?: '—' }}</dd></div>
                        <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800"><dt class="text-zinc-400">Inicio</dt><dd>{{ $empeno->inicio->format('d/m/Y') }}</dd></div>
                        @if ($empeno->categoria)
                            <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800"><dt class="text-zinc-400">Categoría</dt><dd>{{ $empeno->categoria }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Historial de pagos</p>
                    <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @forelse ($empeno->pagos as $p)
                            <li class="flex justify-between py-2">
                                <span class="text-zinc-500">{{ $p->fecha->format('d/m/Y') }} · {{ $p->tipo }}</span>
                                <span class="font-semibold text-emerald-600 tabular-nums dark:text-emerald-400">{{ cop($p->interes + $p->abono) }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-zinc-400">Sin pagos aún</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="space-y-4">
                @if ($activo)
                    <div class="rounded-xl border border-emerald-600/40 bg-emerald-500/10 px-4 py-4 text-center">
                        <div class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Para retirar hoy debe</div>
                        <div class="my-1 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ cop($empeno->deudaHoy()) }}</div>
                        <div class="text-xs text-zinc-500">saldo {{ cop($empeno->saldo) }} + interés corrido {{ cop($empeno->interesCorrido()) }}</div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Registrar pago</p>
                        <div class="mb-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60">
                            Cuota del mes: <b>{{ cop($empeno->interesMes()) }}</b>. Corre el vencimiento +1 mes.
                        </div>
                        <form method="POST" action="{{ route('empenos.pago', $empeno) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Abono a capital (opcional)</label>
                                <input type="number" name="abono" placeholder="0" min="0" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                <p class="mt-1 text-xs text-zinc-400">El abono se paga junto con el interés del mes.</p>
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Registrar pago del mes</button>
                        </form>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('empenos.contrato', $empeno) }}" target="_blank" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Ver contrato</a>
                        <form method="POST" action="{{ route('empenos.retirar', $empeno) }}" onsubmit="return confirm('¿El cliente retira el artículo? El capital vuelve a caja.')">
                            @csrf
                            <button type="submit" class="rounded-lg bg-amber-500/15 px-3 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-500/25 dark:text-amber-400">Cliente retira</button>
                        </form>
                        @if ($empeno->mesesSinPagar() >= $empeno->plazo)
                            <form method="POST" action="{{ route('empenos.perder', $empeno) }}" onsubmit="return confirm('¿Pasar el artículo a inventario para vender?')">
                                @csrf
                                <button type="submit" class="rounded-lg bg-red-500/15 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-500/25 dark:text-red-300">Pasar a inventario</button>
                            </form>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Estado</p>
                        <x-estado :estado="$empeno->estadoCalculado()" />
                        <p class="mt-3 text-sm text-zinc-500">Este empeño está cerrado.</p>
                        <a href="{{ route('empenos.contrato', $empeno) }}" target="_blank" class="mt-3 inline-block rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Ver contrato</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
