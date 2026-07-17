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
                            <li class="flex items-center justify-between gap-3 py-2">
                                <span class="text-zinc-500">{{ $p->fecha->format('d/m/Y') }} · {{ $p->tipo }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-emerald-600 tabular-nums dark:text-emerald-400">{{ cop($p->interes + $p->abono) }}</span>
                                    <a href="{{ route('pagos.recibo', $p) }}" target="_blank" class="rounded-md px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-500/10 dark:text-emerald-400" title="Recibo del pago">🧾 Recibo</a>
                                    @if ($activo && auth()->user()->puedeEditar())
                                        <form method="POST" action="{{ route('pagos.deshacer', $p) }}" onsubmit="return confirm('¿Deshacer este pago? Se revierte el valor de caja y el mes que corrió.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-500/10 dark:text-red-400" title="Deshacer este pago">↶ Deshacer</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="py-2 text-zinc-400">Sin pagos aún</li>
                        @endforelse
                    </ul>
                </div>

                @if ($activo && auth()->user()->puedeEditar())
                    <details class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <summary class="cursor-pointer list-none text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">Editar empeño (número, fecha, artículo)</summary>
                        <p class="mb-3 mt-2 text-xs text-zinc-400">Para corregir datos (útil en migraciones). Solo el dueño.</p>
                        <form method="POST" action="{{ route('empenos.datos', $empeno) }}" class="grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Número de contrato</label>
                                <input type="number" name="numero" value="{{ $empeno->numero }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Fecha de inicio</label>
                                <input type="date" name="inicio" value="{{ $empeno->inicio->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Artículo</label>
                                <input name="articulo" value="{{ $empeno->articulo }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Serial / IMEI / Motor</label>
                                <input name="serial" value="{{ $empeno->serial }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Color</label>
                                <input name="color" value="{{ $empeno->color }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Estado / observaciones</label>
                                <input name="observaciones" value="{{ $empeno->observaciones }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            </div>
                            <div class="sm:col-span-2">
                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Guardar datos</button>
                            </div>
                        </form>
                    </details>
                @endif
            </div>

            <div class="space-y-4">
                @if ($activo)
                    <p class="text-xs font-bold uppercase tracking-widest text-zinc-400">¿Qué va a hacer el cliente?</p>

                    {{-- 1) Paga y conserva --}}
                    <div class="rounded-xl border border-emerald-600/40 bg-white p-5 shadow-sm dark:border-emerald-700/40 dark:bg-zinc-900">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">1 · Paga y conserva el artículo</p>
                        <p class="mb-3 mt-1 text-xs text-zinc-500">Paga el mes y <b>sigue</b> con el empeño. El artículo no se entrega; el vencimiento corre +1 mes.</p>
                        <form method="POST" action="{{ route('empenos.pago', $empeno) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Interés recibido</label>
                                <input type="text" inputmode="numeric" name="interes_recibido" value="{{ $empeno->interesMes() }}" class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                <p class="mt-1 text-xs text-zinc-400">Cuota del mes: {{ cop($empeno->interesMes()) }}. Ajusta si recibiste otro valor.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Abono a capital (opcional)</label>
                                <input type="text" inputmode="numeric" name="abono" placeholder="0" class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                <p class="mt-1 text-xs text-zinc-400">Opcional: baja la deuda. Se paga junto con el interés.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Fecha del pago</label>
                                <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                <p class="mt-1 text-xs text-zinc-400">Hoy por defecto. Para registrar pagos de meses atrás, pon la fecha real en que pagó.</p>
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Registrar pago · conserva el artículo</button>
                        </form>
                    </div>

                    {{-- 2) Retira (desempeño) --}}
                    <div class="rounded-xl border border-amber-500/50 bg-amber-500/5 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500">2 · Retira el artículo (desempeño)</p>
                        <p class="mb-3 mt-1 text-xs text-zinc-500">Paga todo y <b>se lleva</b> el artículo. El empeño se cierra.</p>
                        <div class="mb-3 rounded-lg border border-amber-500/40 bg-white px-4 py-3 text-center dark:bg-zinc-900">
                            <div class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-500">Debe hoy para llevárselo</div>
                            <div class="my-0.5 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ cop($empeno->deudaHoy()) }}</div>
                            <div class="text-xs text-zinc-500">saldo {{ cop($empeno->saldo) }} + interés {{ cop($empeno->interesCorrido()) }}</div>
                        </div>
                        <form method="POST" action="{{ route('empenos.retirar', $empeno) }}" onsubmit="return confirm('¿El cliente se lleva el artículo? El empeño se cierra y el capital vuelve a caja. Este valor YA incluye el interés: no registres el pago del mes aparte.')">
                            @csrf
                            <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Valor recibido</label>
                            <input type="text" inputmode="numeric" name="valor_recibido" value="{{ $empeno->deudaHoy() }}" class="money w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                            <p class="mt-1 text-xs text-zinc-400"><b>Ya incluye el interés que debe</b> — no registres el pago del mes aparte. Ajusta si recibiste otro valor.</p>
                            <button type="submit" class="mt-3 w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-400">Registrar retiro y entregar artículo</button>
                        </form>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('empenos.contrato', $empeno) }}" target="_blank" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Ver contrato</a>
                        @if ($empeno->mesesSinPagar() >= $empeno->plazo)
                            <form method="POST" action="{{ route('empenos.perder', $empeno) }}" onsubmit="return confirm('¿Pasar el artículo a inventario para vender?')">
                                @csrf
                                <button type="submit" class="rounded-lg bg-red-500/15 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-500/25 dark:text-red-300">Pasar a inventario</button>
                            </form>
                        @endif
                    </div>

                    @if (auth()->user()->puedeEditar())
                        <div class="rounded-xl border border-red-500/40 bg-red-500/5 p-5 shadow-sm">
                            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-red-600 dark:text-red-400">Eliminar (creado por error)</p>
                            @if ($empeno->meses_pagados === 0)
                                <p class="mb-3 text-xs text-zinc-500">Solo el dueño. El capital vuelve a caja y queda en el historial.</p>
                                <form method="POST" action="{{ route('empenos.destroy', $empeno) }}" onsubmit="return confirm('¿Eliminar este empeño? El capital vuelve a caja y quedará registrado en el historial.')">
                                    @csrf
                                    @method('DELETE')
                                    <input name="motivo" placeholder="Motivo (opcional)" class="mb-2 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Eliminar empeño</button>
                                </form>
                            @else
                                <p class="text-xs text-zinc-500">Este empeño tiene <b>{{ $empeno->meses_pagados }} pago(s)</b> registrados. Como filtro de seguridad, primero <b>deshaz todos sus pagos</b> (arriba, en “Historial de pagos”, botón <span class="text-red-600">↶ Deshacer</span>). Cuando no quede ningún pago, aquí aparecerá el botón para eliminarlo.</p>
                            @endif
                        </div>
                    @endif
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
