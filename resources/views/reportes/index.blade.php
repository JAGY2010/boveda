<x-layouts::app :title="'Reporte'">
    <div class="mx-auto w-full max-w-4xl">
        <x-flash />

        <div class="mb-4">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Reporte</h1>
            <p class="text-sm text-zinc-500">
                {{ \Illuminate\Support\Carbon::parse($desde)->isoFormat('D MMM YYYY') }}
                @if ($desde !== $hasta) — {{ \Illuminate\Support\Carbon::parse($hasta)->isoFormat('D MMM YYYY') }} @endif
            </p>
        </div>

        {{-- Selector de período --}}
        @php $periodos = ['hoy' => 'Hoy', 'mes' => 'Este mes', 'mespasado' => 'Mes pasado']; @endphp
        <div class="mb-3 flex flex-wrap gap-2">
            @foreach ($periodos as $key => $label)
                <a href="{{ route('reporte.index', ['periodo' => $key]) }}" wire:navigate
                   class="rounded-full px-3 py-1.5 text-sm font-semibold {{ $periodo === $key ? 'bg-emerald-600 text-white' : 'border border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('reporte.index') }}" class="mb-5 flex flex-wrap items-end gap-2">
            <input type="hidden" name="periodo" value="personalizado">
            <div>
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}" max="{{ now()->format('Y-m-d') }}" class="rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-zinc-500">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" max="{{ now()->format('Y-m-d') }}" class="rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <button type="submit" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-white">Ver rango</button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-kpi label="Empeños nuevos" :value="$cantEmpenos" :sub="'prestado ' . cop($prestado)" accent="amber" />
            <x-kpi label="Interés cobrado" :value="cop($interesCobrado)" sub="ganancia del período" accent="emerald" />
            <x-kpi label="Abonos a capital" :value="cop($abonosCapital)" sub="deuda que bajaron" accent="sky" />
            <x-kpi label="Gastos" :value="cop($gastos)" sub="del período" accent="zinc" />
            <x-kpi label="Ganancia del período" :value="cop($ganancia)" sub="interés − gastos" accent="emerald" />
        </div>

        <p class="mt-4 text-xs text-zinc-400">El "prestado" cuenta los empeños por su fecha de inicio. El interés y los abonos cuentan por la fecha del pago. Las ventas se ven en Contabilidad.</p>
    </div>
</x-layouts::app>
