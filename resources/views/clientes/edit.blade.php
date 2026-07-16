<x-layouts::app :title="'Editar cliente'">
    <div class="mx-auto w-full max-w-2xl">
        <a href="{{ route('clientes.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Clientes</a>
        <x-flash />

        <h1 class="mb-1 text-2xl font-semibold text-zinc-900 dark:text-white">Editar cliente</h1>
        <p class="mb-5 text-sm text-zinc-500">Actualiza los datos de contacto si el cliente cambió de número o dirección.</p>

        <form method="POST" action="{{ route('clientes.update', $cliente) }}"
              class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Nombre completo</label>
                <input name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                       class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Cédula</label>
                    <input name="cedula" value="{{ old('cedula', $cliente->cedula) }}"
                           class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Celular</label>
                    <input name="tel" value="{{ old('tel', $cliente->tel) }}"
                           class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Dirección</label>
                <input name="direccion" value="{{ old('direccion', $cliente->direccion) }}"
                       class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Tel. de referencia / 2do contacto</label>
                <input name="contacto2" value="{{ old('contacto2', $cliente->contacto2) }}"
                       class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Guardar cambios</button>
                <a href="{{ route('clientes.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</a>
            </div>
        </form>
    </div>
</x-layouts::app>
