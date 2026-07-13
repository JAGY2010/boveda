<x-layouts::app :title="'Locales'">
    <div class="mx-auto w-full max-w-5xl">
        <x-flash />

        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Locales</h1>
                <p class="text-sm text-zinc-500">{{ $locales->count() }} negocios usando Bóveda</p>
            </div>
            <a href="{{ route('admin.locales.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                + Nuevo local
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-400 dark:border-zinc-800">
                        <th class="px-4 py-3 font-semibold">Local</th>
                        <th class="px-4 py-3 font-semibold">Usuarios</th>
                        <th class="px-4 py-3 text-right font-semibold">Clientes</th>
                        <th class="px-4 py-3 text-right font-semibold">Empeños</th>
                        <th class="px-4 py-3 font-semibold">Suscripción</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($locales as $l)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.locales.show', $l) }}" wire:navigate class="font-semibold text-zinc-900 hover:text-emerald-600 dark:text-white">{{ $l->nombre }}</a>
                                <div class="text-xs text-zinc-400">{{ $l->ciudad }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $l->usuarios_count }}
                                <span class="text-xs text-zinc-400">· {{ $l->duenos_count }}D · {{ $l->empleados_count }}E</span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $l->clientes_count }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $l->empenos_count }}</td>
                            <td class="px-4 py-3"><x-suscripcion-badge :negocio="$l" /></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.locales.show', $l) }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Gestionar</a>
                                    <form method="POST" action="{{ route('local.cambiar') }}">
                                        @csrf
                                        <input type="hidden" name="local_id" value="{{ $l->id }}">
                                        <button type="submit" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Entrar →</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-zinc-400">D = dueños · E = empleados. Entra a un local para ver sus usuarios y su suscripción.</p>
    </div>
</x-layouts::app>
