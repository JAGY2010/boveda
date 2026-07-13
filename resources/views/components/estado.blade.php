@props(['estado'])

@php
    $map = [
        'al dia' => ['Al día', 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'],
        'en mora' => ['En mora', 'bg-amber-500/15 text-amber-700 dark:text-amber-400'],
        'por perder' => ['Por perder', 'bg-red-500/15 text-red-700 dark:text-red-300'],
        'retirado' => ['Retirado', 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-300'],
        'perdido' => ['En inventario', 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-300'],
        'vendido' => ['Vendido', 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-300'],
    ];
    [$label, $cls] = $map[$estado] ?? [$estado, 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-300'];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap {{ $cls }}">{{ $label }}</span>
