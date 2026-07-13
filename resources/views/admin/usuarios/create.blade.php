<x-layouts::app :title="'Nuevo usuario'">
    <div class="mx-auto w-full max-w-2xl">
        <a href="{{ route('admin.usuarios.index') }}" wire:navigate class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-600">← Usuarios</a>
        <x-flash />

        <h1 class="mb-1 text-2xl font-semibold text-zinc-900 dark:text-white">Nuevo usuario</h1>
        <p class="mb-5 text-sm text-zinc-500">Crea un perfil, asígnale un rol y los locales que puede ver.</p>

        <form method="POST" action="{{ route('admin.usuarios.store') }}"
              class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Nombre</label>
                    <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Correo</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Contraseña</label>
                    <input type="text" name="password" required minlength="6" placeholder="mínimo 6 caracteres" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Rol</label>
                    <select name="role" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        <option value="employee">Empleado (solo agrega)</option>
                        <option value="owner">Dueño (ve dinero, edita)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-zinc-600 dark:text-zinc-300">Locales que puede ver</label>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($locales as $l)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <input type="checkbox" name="locales[]" value="{{ $l->id }}" class="rounded" />
                            <span>{{ $l->nombre }} <span class="text-zinc-400">· {{ $l->ciudad }}</span></span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-zinc-400">El empleado normalmente tiene 1 local; el dueño puede tener varios.</p>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Crear usuario</button>
                <a href="{{ route('admin.usuarios.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</a>
            </div>
        </form>
    </div>
</x-layouts::app>
