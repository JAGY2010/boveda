<x-layouts::app :title="'Configuración'">
    <div class="mx-auto w-full max-w-2xl">
        <x-flash />

        <h1 class="mb-1 text-2xl font-semibold text-zinc-900 dark:text-white">Configuración</h1>
        <p class="mb-5 text-sm text-zinc-500">Ajustes del negocio</p>

        <form method="POST" action="{{ route('config.update') }}" enctype="multipart/form-data"
              class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Nombre del negocio</label>
                    <input name="nombre" value="{{ old('nombre', $negocio->nombre) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">NIT</label>
                    <input name="nit" value="{{ old('nit', $negocio->nit) }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Logo del negocio (aparece en el contrato)</label>
                @if ($negocio->logo_data || $negocio->logo_path)
                    <img src="{{ $negocio->logo_data ?: asset($negocio->logo_path) }}" alt="Logo actual" class="mb-2 h-16 rounded border border-zinc-200 bg-white p-1 dark:border-zinc-700" />
                @endif
                <input type="file" name="logo" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white dark:text-zinc-300" />
                <p class="mt-1 text-xs text-zinc-400">PNG o JPG, máx. 2 MB. Al guardar aparecerá en la cabecera del contrato.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Ciudad</label>
                    <input name="ciudad" value="{{ old('ciudad', $negocio->ciudad) }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Teléfono</label>
                    <input name="telefono" value="{{ old('telefono', $negocio->telefono) }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Representante / dueño (para el contrato)</label>
                    <input name="representante" value="{{ old('representante', $negocio->representante) }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Dirección del local</label>
                    <input name="direccion" value="{{ old('direccion', $negocio->direccion) }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Plazo (meses)</label>
                    <input type="number" name="plazo_default" value="{{ old('plazo_default', $negocio->plazo_default) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Interés % (def.)</label>
                    <input type="number" step="0.01" name="pct_default" value="{{ old('pct_default', $negocio->pct_default) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">% préstamo sugerido</label>
                    <input type="number" name="ltv_default" value="{{ old('ltv_default', $negocio->ltv_default) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">N° inicial del talonario (consecutivo)</label>
                <input type="number" name="consecutivo_inicial" value="{{ old('consecutivo_inicial', $negocio->consecutivo_inicial) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                <p class="mt-1 text-xs text-zinc-400">El próximo contrato usa este número. Cada local tiene su numeración; ponle el número con el que arranca su talonario.</p>
            </div>

            <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                <input type="checkbox" name="sms_activo" value="1" @checked($negocio->sms_activo) class="rounded" />
                Enviar recordatorios por SMS
            </label>
            <p class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60">
                El interés queda libre; el sistema no bloquea. El “% préstamo sugerido” es cuánto prestas frente al valor estimado del artículo.
            </p>

            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Guardar</button>
        </form>
    </div>
</x-layouts::app>
