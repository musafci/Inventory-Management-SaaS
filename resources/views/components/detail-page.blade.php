@props([
    'title',
    'backUrl',
    'backLabel' => 'Back to list',
])

<div class="mb-6">
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-primary-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        {{ $backLabel }}
    </a>
    <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h2>
</div>

<div class="card">
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
