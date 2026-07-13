<x-layouts::app :title="'Nuevo local'">
    <div class="mx-auto w-full max-w-2xl">
        <a href="{{ route('admin.locales.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Locales</a>
        <x-flash />

        <h1 class="mb-1 text-2xl font-semibold text-zinc-900 dark:text-white">Nuevo local</h1>
        <p class="mb-5 text-sm text-zinc-500">Da de alta un negocio que compra el servicio.</p>

        <form method="POST" action="{{ route('admin.locales.store') }}"
              class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Nombre del local</label>
                <input name="nombre" value="{{ old('nombre') }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Ciudad</label>
                    <input name="ciudad" value="{{ old('ciudad') }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">NIT</label>
                    <input name="nit" value="{{ old('nit') }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Plazo (meses)</label>
                    <input type="number" name="plazo_default" value="{{ old('plazo_default', 4) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Interés % (def.)</label>
                    <input type="number" step="0.01" name="pct_default" value="{{ old('pct_default', 20) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">% préstamo sug.</label>
                    <input type="number" name="ltv_default" value="{{ old('ltv_default', 50) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Capital inicial en caja</label>
                    <input type="number" name="caja_inicial" value="{{ old('caja_inicial', 0) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">N° inicial del talonario</label>
                    <input type="number" name="consecutivo_inicial" value="{{ old('consecutivo_inicial', 1) }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    <p class="mt-1 text-xs text-zinc-400">Con el que arranca su numeración propia.</p>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Dueño (opcional)</label>
                <select name="owner_id" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    <option value="">— sin asignar por ahora —</option>
                    @foreach ($duenos as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->email }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-400">Puedes crear el dueño después en Usuarios y asignarle este local.</p>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Crear local</button>
                <a href="{{ route('admin.locales.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</a>
            </div>
        </form>
    </div>
</x-layouts::app>
