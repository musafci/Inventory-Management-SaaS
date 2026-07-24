@props([
    'placeholder' => 'Search...',
])

<div class="flex-1 max-w-lg">
    <div class="relative group">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 transition-colors group-focus-within:text-primary-500">
            <svg class="h-4.5 w-4.5 text-slate-400 transition-colors group-focus-within:text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        </div>
        <input
            type="text"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge([
                'class' => 'search-input',
                'wire:loading.class' => 'search-loading',
                'wire:target' => 'search',
            ]) }}
        />
    </div>
</div>
