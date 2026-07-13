@if (session('ok'))
    <div class="mb-4 rounded-xl border border-emerald-600/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-300">
        {{ session('ok') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-xl border border-red-600/30 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-700 dark:text-red-300">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-red-600/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif
