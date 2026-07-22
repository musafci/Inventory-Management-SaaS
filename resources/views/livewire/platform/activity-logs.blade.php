<div>
    <div class="page-banner mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-100">Platform audit trail</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">Activity log analysis</h2>
                <p class="mt-2 text-sm text-primary-100/80">Investigate tenant actions across orders, payments, stock, and roles.</p>
            </div>
            <button type="button" wire:click="applyFilters" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm transition-all hover:bg-white/25">
                Refresh
            </button>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card stat-card-indigo">
            <p class="text-sm font-medium text-slate-500">Total events</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($summary['total'] ?? 0) }}</p>
        </div>
        <div class="stat-card stat-card-emerald">
            <p class="text-sm font-medium text-slate-500">Last 24 hours</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($summary['last_24_hours'] ?? 0) }}</p>
        </div>
        <div class="stat-card stat-card-amber">
            <p class="text-sm font-medium text-slate-500">Last 7 days</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($summary['last_7_days'] ?? 0) }}</p>
        </div>
        <div class="stat-card stat-card-sky">
            <p class="text-sm font-medium text-slate-500">Event types tracked</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ count($summary['by_event'] ?? []) }}</p>
        </div>
    </div>

    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">By event</h3>
            <div class="mt-4 space-y-2">
                @forelse($summary['by_event'] ?? [] as $row)
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-2 text-sm">
                        <span class="font-medium capitalize text-slate-700">{{ $row['event'] }}</span>
                        <span class="badge badge-info">{{ number_format($row['count']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">By resource type</h3>
            <div class="mt-4 space-y-2">
                @forelse($summary['by_subject_type'] ?? [] as $row)
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-2 text-sm">
                        <span class="font-medium text-slate-700">{{ $row['subject_type'] }}</span>
                        <span class="badge badge-info">{{ number_format($row['count']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if(! empty($summary['top_organizations']))
        <div class="card mb-8 p-6">
            <h3 class="text-lg font-semibold text-slate-900">Most active organizations</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="table-modern min-w-full">
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th class="text-right">Events</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['top_organizations'] as $row)
                            <tr wire:key="top-org-{{ $row['organization_id'] }}">
                                <td>
                                    <a href="{{ route('platform.organizations.show', $row['organization_id']) }}" class="font-medium text-primary-600 hover:text-primary-500">
                                        {{ $row['organization_name'] ?? 'Organization #'.$row['organization_id'] }}
                                    </a>
                                </td>
                                <td class="text-right">{{ number_format($row['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card p-6">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Activity feed</h3>
                <p class="mt-1 text-sm text-slate-500">Filter and inspect individual audit entries.</p>
            </div>
            <button type="button" wire:click="clearFilters" class="btn-secondary">Clear filters</button>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="form-label">Search</label>
                <input type="text" wire:model.live.debounce.400ms="filters.search" class="form-input" placeholder="Description or log name">
            </div>
            <div>
                <label class="form-label">Event</label>
                <select wire:model.live="filters.event" class="form-input">
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>
            <div>
                <label class="form-label">Resource type</label>
                <select wire:model.live="filters.subject_type" class="form-input">
                    <option value="">All types</option>
                    @foreach($subjectTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Organization ID</label>
                <input type="number" wire:model.live.debounce.400ms="filters.organization_id" class="form-input" placeholder="Filter by tenant ID">
            </div>
            <div>
                <label class="form-label">From</label>
                <input type="date" wire:model.live="filters.from" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" wire:model.live="filters.to" class="form-input">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-modern min-w-full">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Organization</th>
                        <th>Event</th>
                        <th>Resource</th>
                        <th>Actor</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr wire:key="activity-{{ $item['id'] }}">
                            <td class="whitespace-nowrap text-sm text-slate-600">
                                {{ \Illuminate\Support\Carbon::parse($item['created_at'])->format('M j, Y g:i A') }}
                            </td>
                            <td>
                                @if(! empty($item['organization_id']))
                                    <a href="{{ route('platform.organizations.show', $item['organization_id']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                        {{ $item['organization']['name'] ?? '#'.$item['organization_id'] }}
                                    </a>
                                @else
                                    <span class="text-sm text-slate-400">—</span>
                                @endif
                            </td>
                            <td><span class="badge badge-info capitalize">{{ $item['event'] ?? '—' }}</span></td>
                            <td class="text-sm text-slate-700">
                                <span class="font-medium">{{ $item['subject']['type'] ?? '—' }}</span>
                                @if(! empty($item['subject']['label']))
                                    <span class="block text-xs text-slate-500">{{ $item['subject']['label'] }}</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-700">
                                {{ $item['causer']['name'] ?? 'System' }}
                                @if(! empty($item['causer']['email']))
                                    <span class="block text-xs text-slate-500">{{ $item['causer']['email'] }}</span>
                                @endif
                            </td>
                            <td class="max-w-xs text-xs text-slate-500">
                                @php
                                    $attributes = $item['changes']['attributes'] ?? null;
                                    $old = $item['changes']['old'] ?? null;
                                @endphp
                                @if(is_array($attributes) && count($attributes) > 0)
                                    @foreach(collect($attributes)->take(3) as $field => $value)
                                        <div>
                                            <span class="font-medium text-slate-600">{{ $field }}:</span>
                                            @if(is_array($old) && array_key_exists($field, $old))
                                                {{ is_scalar($old[$field]) ? $old[$field] : json_encode($old[$field]) }}
                                                →
                                            @endif
                                            {{ is_scalar($value) ? $value : json_encode($value) }}
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-slate-400">{{ $item['description'] ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm text-slate-500">No activity logs match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(! empty($pagination['last_page']) && $pagination['last_page'] > 1)
            <div class="mt-6 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Page {{ $pagination['current_page'] ?? 1 }} of {{ $pagination['last_page'] ?? 1 }}
                    · {{ number_format($pagination['total'] ?? 0) }} total
                </p>
                <div class="flex gap-2">
                    @if(($pagination['current_page'] ?? 1) > 1)
                        <button type="button" wire:click="goToPage({{ ($pagination['current_page'] ?? 1) - 1 }})" class="btn-secondary">Previous</button>
                    @endif
                    @if(($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                        <button type="button" wire:click="goToPage({{ ($pagination['current_page'] ?? 1) + 1 }})" class="btn-secondary">Next</button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
