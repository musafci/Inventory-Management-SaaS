@props(['status' => 'trial'])

@php
    $badgeClass = match ($status) {
        'active' => 'badge-success',
        'suspended' => 'badge-danger',
        default => 'badge-warning',
    };
@endphp

<span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
