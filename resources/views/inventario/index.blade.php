<x-layouts::app :title="'Ventas e inventario'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Ventas e inventario</h1>
            <p class="text-sm text-zinc-500">{{ $disponibles->count() }} para vender · {{ $separados->count() }} separados · {{ $vendidos->count() }} vendidos</p>
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
                        <th class="px-4 py-3 text-right font-semibold">Vender / separar</th>
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
                                <div class="mt-1 text-right">
                                    <button type="button" onclick="abrirSeparar({{ $it->id }}, @js($it->descripcion), {{ (int) $it->costo }})"
                                            class="text-xs font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-500">+ Separar para un cliente</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-zinc-400">Nada por vender. Los artículos no retirados o comprados directo aparecen aquí.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($separados->count())
            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-zinc-400">Separados (abonando)</p>
            <div class="mb-6 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full min-w-[620px] text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                            <th class="px-4 py-3 font-semibold">Artículo</th>
                            <th class="px-4 py-3 font-semibold">Cliente</th>
                            <th class="px-4 py-3 text-right font-semibold">Precio</th>
                            <th class="px-4 py-3 text-right font-semibold">Abonado</th>
                            <th class="px-4 py-3 text-right font-semibold">Falta</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($separados as $sep)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $sep->item->descripcion }}</div>
                                    <div class="mt-1 h-1.5 w-32 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div class="h-full rounded-full {{ $sep->estaPago() ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $sep->porcentaje() }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $sep->cliente->nombre }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ cop($sep->precio) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-emerald-600">{{ cop($sep->abonado) }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $sep->estaPago() ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $sep->estaPago() ? 'Pago' : cop($sep->saldo()) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('separados.show', $sep) }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Abrir</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

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

    {{-- Separar un artículo: se elige cliente existente o se crea uno nuevo --}}
    <div id="sepModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-1 text-lg font-semibold text-zinc-900 dark:text-white">Separar artículo</h2>
            <p id="sepArt" class="mb-4 text-sm text-zinc-500"></p>

            <form method="POST" id="sepForm" class="space-y-4">
                @csrf

                <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="button" id="sep_tab_ex" onclick="sepCli('ex')" class="rounded-md px-3 py-1.5 text-sm font-semibold">Existente</button>
                    <button type="button" id="sep_tab_nu" onclick="sepCli('nu')" class="rounded-md px-3 py-1.5 text-sm font-semibold">Nuevo</button>
                </div>

                <div id="sep_ex">
                    @if ($clientes->count())
                        <select name="cliente_id" id="sep_cliente_id" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @foreach ($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}@if ($c->cedula) — {{ $c->cedula }}@endif</option>
                            @endforeach
                        </select>
                    @else
                        <p class="text-sm text-zinc-500">No hay clientes aún. Usa la pestaña “Nuevo”.</p>
                    @endif
                </div>

                <div id="sep_nu" class="hidden space-y-3">
                    <input name="nuevo_nombre" id="sep_nuevo_nombre" placeholder="Nombre completo" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="nuevo_cedula" placeholder="Cédula" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                        <input name="nuevo_tel" placeholder="Celular" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                    <input name="nuevo_direccion" placeholder="Dirección" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-zinc-500">Precio pactado</label>
                        <input type="text" inputmode="numeric" name="precio" id="sep_precio" required class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-zinc-500">Abono de entrada</label>
                        <input type="text" inputmode="numeric" name="abono_inicial" placeholder="0" class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-zinc-500">Fecha</label>
                        <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" onclick="cerrarSeparar()" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 dark:border-zinc-600 dark:text-zinc-200">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">Separar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function sepCli(cual) {
            const ex = document.getElementById('sep_ex');
            const nu = document.getElementById('sep_nu');
            ex.classList.toggle('hidden', cual !== 'ex');
            nu.classList.toggle('hidden', cual === 'ex');

            // Solo viaja el bloque activo: si hay nombre nuevo, el backend lo crea.
            const sel = document.getElementById('sep_cliente_id');
            if (sel) sel.disabled = (cual !== 'ex');
            document.getElementById('sep_nuevo_nombre').disabled = (cual === 'ex');

            const on = 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white';
            document.getElementById('sep_tab_ex').className = 'rounded-md px-3 py-1.5 text-sm font-semibold ' + (cual === 'ex' ? on : 'text-zinc-500');
            document.getElementById('sep_tab_nu').className = 'rounded-md px-3 py-1.5 text-sm font-semibold ' + (cual === 'nu' ? on : 'text-zinc-500');
        }

        function abrirSeparar(id, descripcion, costo) {
            const m = document.getElementById('sepModal');
            document.getElementById('sepForm').action = '{{ url('inventario') }}/' + id + '/separar';
            document.getElementById('sepArt').textContent = descripcion;
            // Mismo criterio que el placeholder de venta: costo + 40%.
            document.getElementById('sep_precio').value = new Intl.NumberFormat('es-CO').format(Math.round(costo * 1.4));
            m.classList.remove('hidden');
            m.classList.add('flex');
            sepCli(@js($clientes->count()) ? 'ex' : 'nu');
        }

        function cerrarSeparar() {
            const m = document.getElementById('sepModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
    </script>
</x-layouts::app>
