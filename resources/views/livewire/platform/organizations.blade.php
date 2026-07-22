<div class="min-w-0 max-w-full">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Organizations</h2>
            <p class="mt-1 text-sm text-slate-500">Manage tenant accounts, subscription plans, and access status.</p>
        </div>
        <button type="button" wire:click="loadItems" wire:loading.attr="disabled" class="btn-secondary shrink-0">
            <svg wire:loading.remove wire:target="loadItems,updatedSearch,updatedStatusFilter,goToPage" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            <svg wire:loading wire:target="loadItems,updatedSearch,updatedStatusFilter,goToPage" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            Refresh
        </button>
    </div>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center">
        <div class="relative flex-1 max-w-lg">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" class="search-input" placeholder="Search by name, email, or slug...">
        </div>
        <div class="tab-nav max-w-md flex-1">
            <button type="button" wire:click="$set('statusFilter', '')" @class(['tab-btn', 'tab-btn-active' => $statusFilter === '', 'tab-btn-inactive' => $statusFilter !== ''])>All</button>
            <button type="button" wire:click="$set('statusFilter', 'active')" @class(['tab-btn', 'tab-btn-active' => $statusFilter === 'active', 'tab-btn-inactive' => $statusFilter !== 'active'])>Active</button>
            <button type="button" wire:click="$set('statusFilter', 'trial')" @class(['tab-btn', 'tab-btn-active' => $statusFilter === 'trial', 'tab-btn-inactive' => $statusFilter !== 'trial'])>Trial</button>
            <button type="button" wire:click="$set('statusFilter', 'suspended')" @class(['tab-btn', 'tab-btn-active' => $statusFilter === 'suspended', 'tab-btn-inactive' => $statusFilter !== 'suspended'])>Suspended</button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="loadItems,updatedSearch,updatedStatusFilter,goToPage">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Organization</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Users</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($items as $item)
                        <tr class="table-row-hover">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $item['slug'] }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <p>{{ $item['email'] ?? '—' }}</p>
                                @if(! empty($item['phone']))
                                    <p class="text-xs">{{ $item['phone'] }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                <span class="badge badge-info">{{ ucfirst($item['plan'] ?? 'trial') }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @include('components.platform-status-badge', ['status' => $item['status'] ?? 'trial'])
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ $item['users_count'] ?? 0 }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a href="{{ route('platform.organizations.show', $item['id']) }}" class="font-semibold text-primary-600 hover:text-primary-500">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="empty-state">
                                    <p class="text-sm font-medium text-slate-900">No organizations found</p>
                                    <p class="mt-1 text-sm text-slate-500">Try adjusting your search or status filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(! empty($pagination) && ($pagination['last_page'] ?? 1) > 1)
            <div class="flex items-center justify-between border-t border-slate-100 px-6 py-4">
                <p class="text-sm text-slate-500">
                    Page {{ $pagination['current_page'] ?? 1 }} of {{ $pagination['last_page'] ?? 1 }}
                    · {{ $pagination['total'] ?? 0 }} total
                </p>
                <div class="flex gap-2">
                    @if(($pagination['current_page'] ?? 1) > 1)
                        <button type="button" wire:click="goToPage({{ ($pagination['current_page'] ?? 1) - 1 }})" class="btn-secondary !px-3 !py-1.5 text-xs">Previous</button>
                    @endif
                    @if(($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                        <button type="button" wire:click="goToPage({{ ($pagination['current_page'] ?? 1) + 1 }})" class="btn-secondary !px-3 !py-1.5 text-xs">Next</button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
