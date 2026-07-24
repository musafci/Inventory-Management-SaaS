<div>
    {{-- Welcome banner --}}
    <div class="page-banner">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-100">Platform control center</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">{{ session('platform_admin_name', 'Admin') }}</h2>
                <p class="mt-2 text-sm text-primary-100/80">Monitor tenants, plans, and account status across the platform.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" wire:click="refresh" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm transition-all hover:bg-white/25 disabled:opacity-60">
                    <svg wire:loading.remove wire:target="refresh" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    <svg wire:loading wire:target="refresh" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Refresh
                </button>
                <a href="{{ route('platform.organizations.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition-all hover:bg-primary-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                    All organizations
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('platform.organizations.index') }}" class="stat-card stat-card-primary group">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 ring-1 ring-primary-100 transition-transform group-hover:scale-105">
                    <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Total tenants</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('platform.organizations.index', ['status' => 'active']) }}" class="stat-card stat-card-emerald group">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100 transition-transform group-hover:scale-105">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Active</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['active']) }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('platform.organizations.index', ['status' => 'trial']) }}" class="stat-card stat-card-amber group">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 ring-1 ring-amber-100 transition-transform group-hover:scale-105">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Trial</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['trial']) }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('platform.organizations.index', ['status' => 'suspended']) }}" class="stat-card stat-card-sky group">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 ring-1 ring-red-100 transition-transform group-hover:scale-105">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Suspended</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['suspended']) }}</p>
                </div>
            </div>
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <h3 class="card-title">Recent organizations</h3>
            <a href="{{ route('platform.organizations.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-500">View all</a>
        </div>
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="refresh">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrganizations as $org)
                        <tr class="table-row-hover">
                            <td>
                                <p class="font-semibold text-slate-900">{{ $org['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $org['slug'] }}</p>
                            </td>
                            <td class="text-slate-600">{{ ucfirst($org['plan'] ?? 'trial') }}</td>
                            <td>@include('components.platform-status-badge', ['status' => $org['status'] ?? 'trial'])</td>
                            <td class="text-slate-600">{{ $org['users_count'] ?? 0 }}</td>
                            <td class="text-right">
                                <a href="{{ route('platform.organizations.show', $org['id']) }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Manage →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state text-sm text-slate-500">No organizations registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
