@props(['usuario'])

<details class="inline-block text-left">
    <summary class="cursor-pointer list-none rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
        Restablecer contraseña
    </summary>
    <form method="POST" action="{{ route('admin.usuarios.password', $usuario) }}" class="mt-2 flex flex-wrap items-center gap-2">
        @csrf
        <input type="text" name="password" required minlength="6" placeholder="Nueva contraseña"
               class="w-44 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500">Guardar</button>
    </form>
    <p class="mt-1 text-xs text-zinc-400">Escribe la nueva clave y entrégasela a la persona. Mínimo 6 caracteres.</p>
</details>
