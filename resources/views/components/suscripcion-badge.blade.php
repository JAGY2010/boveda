@props(['negocio'])

@php
    $est = $negocio->estadoSuscripcion();
    $clases = [
        'activa' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
        'por_vencer' => 'bg-amber-500/15 text-amber-700 dark:text-amber-500',
        'vencida' => 'bg-red-500/15 text-red-700 dark:text-red-400',
        'suspendida' => 'bg-zinc-500/20 text-zinc-600 dark:text-zinc-300',
    ][$est] ?? 'bg-zinc-500/15 text-zinc-600';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {$clases}"]) }}>
    {{ $negocio->estadoLabel() }}
</span>
