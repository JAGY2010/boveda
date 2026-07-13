@props(['label', 'value', 'sub' => null, 'accent' => 'emerald'])

@php
    $bar = [
        'emerald' => 'border-t-emerald-500',
        'amber' => 'border-t-amber-500',
        'zinc' => 'border-t-zinc-400',
        'sky' => 'border-t-sky-500',
    ][$accent] ?? 'border-t-emerald-500';
@endphp

<div class="rounded-xl border border-t-2 {{ $bar }} border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="text-xs font-bold uppercase tracking-wide text-zinc-400">{{ $label }}</div>
    <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $value }}</div>
    @if ($sub)
        <div class="mt-0.5 text-xs text-zinc-400">{{ $sub }}</div>
    @endif
</div>
