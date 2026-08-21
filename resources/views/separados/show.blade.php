<x-layouts::app :title="'Separado · ' . $separado->item->descripcion">
    <div class="mx-auto w-full max-w-3xl">
        <a href="{{ route('inventario.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Ventas e inventario</a>
        <x-flash />

        @if (session('recibo_id'))
            <div class="mb-4 flex items-center justify-between rounded-xl border border-emerald-600/40 bg-emerald-500/10 px-4 py-3">
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Comprobante del abono listo</span>
                <a href="{{ route('separados.recibo', session('recibo_id')) }}" target="_blank"
                   class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500">🖨 Imprimir</a>
            </div>
        @endif

        <div class="mb-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $separado->item->descripcion }}</h1>
                    <p class="text-sm text-zinc-500">
                        {{ $separado->cliente->nombre }}@if ($separado->cliente->cedula) · C.C. {{ $separado->cliente->cedula }}@endif
                        @if ($separado->cliente->tel) · {{ $separado->cliente->tel }} @endif
                    </p>
                    <p class="mt-1 text-xs text-zinc-400">Separado el {{ $separado->fecha_inicio->format('d/m/Y') }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide
                    {{ $separado->estado === 'activo' ? ($separado->estaPago() ? 'bg-emerald-500/15 text-emerald-600' : 'bg-amber-500/15 text-amber-600') : 'bg-zinc-500/15 text-zinc-500' }}">
                    {{ $separado->estado === 'activo' ? ($separado->estaPago() ? 'Pago · listo para entregar' : 'Abonando') : ucfirst($separado->estado) }}
                </span>
            </div>

            <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full {{ $separado->estaPago() ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $separado->porcentaje() }}%"></div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-xs uppercase tracking-wide text-zinc-400">Precio</div>
                    <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-white">{{ cop($separado->precio) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-zinc-400">Abonado</div>
                    <div class="text-lg font-semibold tabular-nums text-emerald-600">{{ cop($separado->abonado) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-zinc-400">Falta</div>
                    <div class="text-lg font-semibold tabular-nums {{ $separado->estaPago() ? 'text-emerald-600' : 'text-amber-600' }}">{{ cop($separado->saldo()) }}</div>
                </div>
            </div>
        </div>

        @if ($separado->estado === 'activo')
            <div class="mb-5 grid gap-4 sm:grid-cols-2">
                @unless ($separado->estaPago())
                    <form method="POST" action="{{ route('separados.abonar', $separado) }}"
                          class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        @csrf
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Registrar abono</p>
                        <input type="text" inputmode="numeric" name="monto" required placeholder="{{ number_format($separado->saldo(), 0, ',', '.') }}"
                               class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Abonar</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('separados.entregar', $separado) }}"
                          class="space-y-3 rounded-xl border border-emerald-600/40 bg-emerald-500/10 p-4">
                        @csrf
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Entregar artículo</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Ya está pago. Al entregar sale del inventario y se registra la ganancia.</p>
                        <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Entregar</button>
                    </form>
                @endunless

                @if (auth()->user()->puedeEditar())
                    <form method="POST" action="{{ route('separados.cancelar', $separado) }}"
                          onsubmit="return confirm('¿Cancelar el separado? El artículo vuelve a estar para vender.')"
                          class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        @csrf
                        <p class="text-xs font-bold uppercase tracking-widest text-red-600">El cliente desiste</p>
                        <label class="block text-xs font-semibold text-zinc-500">Cuánto se le devuelve (máx. {{ cop($separado->abonado) }})</label>
                        <input type="text" inputmode="numeric" name="devuelto" required value="0"
                               class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <button type="submit" class="w-full rounded-lg border border-red-600/40 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-600/10">Cancelar separado</button>
                    </form>
                @endif
            </div>
        @endif

        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-zinc-400">Abonos</p>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 text-right font-semibold">Monto</th>
                        <th class="px-4 py-3 text-right font-semibold">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($separado->abonos as $ab)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ $ab->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-600">{{ cop($ab->monto) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('separados.recibo', $ab) }}" target="_blank" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">🖨 Recibo</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-zinc-400">Sin abonos todavía</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
