<div class="flex h-16 items-center px-0 pt-6 mb-2">
    <a href="{{ route('platform.dashboard') }}" class="block transition-opacity hover:opacity-90">
        <x-app-logo size="sm" />
    </a>
</div>

<nav class="sidebar-scroll flex flex-1 flex-col gap-0.5 overflow-y-auto pb-4">
    @php $current = request()->route()?->getName() ?? ''; @endphp

    <div class="nav-section-label">Platform</div>

    <a href="{{ route('platform.dashboard') }}"
       class="nav-link {{ str_starts_with($current, 'platform.dashboard') ? 'nav-link-active' : 'nav-link-inactive' }}">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
        Dashboard
    </a>

    <a href="{{ route('platform.organizations.index') }}"
       class="nav-link {{ str_contains($current, 'platform.organizations') ? 'nav-link-active' : 'nav-link-inactive' }}">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
        Organizations
    </a>

    <div class="nav-section-label mt-6">Switch app</div>
    <a href="/login" class="nav-link nav-link-inactive">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
        Tenant portal
    </a>
</nav>
