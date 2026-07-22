@props([
    'size' => 'md',
    'showText' => true,
])

@php
    $sizes = [
        'sm' => [
            'icon' => 'h-8 w-8',
            'svg' => 'h-4 w-4',
            'img' => 'h-8',
            'text' => 'text-base',
        ],
        'md' => [
            'icon' => 'h-10 w-10',
            'svg' => 'h-5 w-5',
            'img' => 'h-10',
            'text' => 'text-lg',
        ],
        'lg' => [
            'icon' => 'h-12 w-12',
            'svg' => 'h-7 w-7',
            'img' => 'h-12',
            'text' => 'text-xl',
        ],
    ];

    $dimension = $sizes[$size] ?? $sizes['md'];
    $hasLogo = is_file(public_path('oneapp.png'));
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    @if ($hasLogo)
        <img
            src="{{ asset('oneapp.png') }}"
            alt="Oneapp"
            class="{{ $dimension['img'] }} w-auto object-contain"
        />
    @else
        <div class="sidebar-brand-icon flex {{ $dimension['icon'] }} flex-shrink-0 items-center justify-center rounded-xl">
            <svg class="{{ $dimension['svg'] }} text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
        </div>
    @endif

    @if ($showText && ! $hasLogo)
        <span class="font-bold tracking-tight text-white {{ $dimension['text'] }}">Oneapp</span>
    @endif
</div>
