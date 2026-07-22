<div>
    <div class="mb-6">
        <a href="{{ route('platform.organizations.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Back to organizations
        </a>
    </div>

    <div class="page-banner mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-100">Tenant #{{ $organization['id'] ?? $organizationId }}</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $organization['name'] ?? 'Organization' }}</h2>
                <p class="mt-2 text-sm text-primary-100/80">{{ $organization['email'] ?? 'No contact email' }} · {{ $organization['users_count'] ?? 0 }} team members</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('components.platform-status-badge', ['status' => $organization['status'] ?? 'trial'])
                <span class="badge badge-info">{{ ucfirst($organization['subscription']['plan']['name'] ?? $organization['plan'] ?? 'trial') }} plan</span>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Tenant controls</h3>
                    <p class="mt-1 text-sm text-slate-500">Update platform access status. Plan changes are managed in the Subscription section below.</p>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <button type="button"
                        @click="$store.confirm.open('Activate tenant', @js('Set ' . ($organization['name'] ?? 'this organization') . ' to active status?'), 'info', () => $wire.applyStatus('active'), 'Activate')"
                        @class(['action-tile !flex-row !items-center !p-4', 'opacity-50 pointer-events-none' => ($organization['status'] ?? '') === 'active'])>
                    <div class="action-tile-icon bg-emerald-50 ring-1 ring-emerald-100"><svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    <span class="text-sm font-semibold text-slate-700">Activate</span>
                </button>
                <button type="button"
                        @click="$store.confirm.open('Set trial', @js('Move ' . ($organization['name'] ?? 'this organization') . ' back to trial?'), 'warning', () => $wire.applyStatus('trial'), 'Set trial')"
                        @class(['action-tile !flex-row !items-center !p-4', 'opacity-50 pointer-events-none' => ($organization['status'] ?? '') === 'trial'])>
                    <div class="action-tile-icon bg-amber-50 ring-1 ring-amber-100"><svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    <span class="text-sm font-semibold text-slate-700">Set trial</span>
                </button>
                <button type="button"
                        @click="$store.confirm.open('Suspend tenant', @js('Suspend ' . ($organization['name'] ?? 'this organization') . '? Users may lose access.'), 'danger', () => $wire.applyStatus('suspended'), 'Suspend')"
                        @class(['action-tile !flex-row !items-center !p-4', 'opacity-50 pointer-events-none' => ($organization['status'] ?? '') === 'suspended'])>
                    <div class="action-tile-icon bg-red-50 ring-1 ring-red-100"><svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg></div>
                    <span class="text-sm font-semibold text-slate-700">Suspend</span>
                </button>
            </div>

            <form wire:submit.prevent="save" class="space-y-5 border-t border-slate-100 pt-6">
                <div>
                    <label class="form-label">Status</label>
                    <select wire:model="form.status" class="form-input">
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    @error('form.status') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="loadOrganization" class="btn-secondary" wire:loading.attr="disabled">
                        Reset
                    </button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save,applyStatus">Save changes</span>
                        <span wire:loading wire:target="save,applyStatus">Saving...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Organization details</h3>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Slug</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization['slug'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $organization['email'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Phone</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $organization['phone'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Team members</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization['users_count'] ?? 0 }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Trial ends</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            @if(! empty($organization['trial_ends_at']))
                                {{ \Illuminate\Support\Carbon::parse($organization['trial_ends_at'])->format('M j, Y') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">Subscription</h3>
            <p class="mt-1 text-sm text-slate-500">Plan limits are enforced on tenant write operations.</p>
            <form wire:submit.prevent="saveSubscription" class="mt-5 space-y-4">
                <div>
                    <label class="form-label">Plan</label>
                    <select wire:model="subscriptionForm.plan_id" class="form-input">
                        <option value="">Select plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan['id'] }}">{{ $plan['name'] }} ({{ $plan['slug'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Subscription status</label>
                    <select wire:model="subscriptionForm.status" class="form-input">
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="past_due">Past due</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">Update subscription</button>
            </form>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">Impersonation</h3>
            <p class="mt-1 text-sm text-slate-500">Issue a short-lived tenant token for support. All sessions are logged.</p>
            <form wire:submit.prevent="startImpersonation" class="mt-5 space-y-4">
                <div>
                    <label class="form-label">Team member</label>
                    <select wire:model="impersonationForm.user_id" class="form-input">
                        <option value="">Select user</option>
                        @foreach(($organization['members'] ?? []) as $member)
                            <option value="{{ $member['id'] }}">{{ $member['name'] }} ({{ $member['email'] }})</option>
                        @endforeach
                    </select>
                    @error('impersonationForm.user_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Reason (required)</label>
                    <textarea wire:model="impersonationForm.reason" rows="3" class="form-input" placeholder="Support ticket #123 — investigating sync issue"></textarea>
                    @error('impersonationForm.reason') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary" wire:loading.attr="disabled">Start impersonation</button>
            </form>
            @if(session('impersonation_token'))
                <div class="mt-4 rounded-xl bg-amber-50 p-4 text-xs text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">Impersonation token issued</p>
                    <p class="mt-2 break-all font-mono">{{ session('impersonation_token') }}</p>
                    <p class="mt-2">Use header <code class="rounded bg-amber-100 px-1">X-Organization-Id: {{ session('impersonation_org_id') }}</code></p>
                </div>
            @endif
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">Feature flags</h3>
            <div class="mt-4 space-y-3">
                @forelse($featureFlags as $flag)
                    <label class="flex items-start justify-between gap-4 rounded-xl border border-slate-100 p-4" wire:key="flag-{{ $flag['id'] }}">
                        <span>
                            <span class="font-medium text-slate-900">{{ $flag['key'] }}</span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $flag['description'] }}</span>
                        </span>
                        <input type="checkbox"
                               class="mt-1 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                               @checked($flag['enabled'])
                               wire:change="toggleFeatureFlag({{ $flag['id'] }}, $event.target.checked)">
                    </label>
                @empty
                    <p class="text-sm text-slate-500">No feature flags configured.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-6">
            <h3 class="text-lg font-semibold text-slate-900">Support notes</h3>
            <p class="mt-1 text-sm text-slate-500">Internal only — never exposed to tenant API.</p>
            <form wire:submit.prevent="addSupportNote" class="mt-5 space-y-3">
                <textarea wire:model="noteForm.note" rows="3" class="form-input" placeholder="Add an internal note..."></textarea>
                @error('noteForm.note') <p class="form-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn-secondary" wire:loading.attr="disabled">Add note</button>
            </form>
            <div class="mt-6 space-y-4">
                @forelse($supportNotes as $note)
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100" wire:key="note-{{ $note['id'] }}">
                        <p class="text-sm text-slate-700">{{ $note['note'] }}</p>
                        <p class="mt-2 text-xs text-slate-400">
                            {{ $note['platform_admin']['name'] ?? 'Platform admin' }}
                            · {{ \Illuminate\Support\Carbon::parse($note['created_at'])->format('M j, Y g:i A') }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No support notes yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 card p-6">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Activity audit</h3>
                <p class="mt-1 text-sm text-slate-500">Organization-scoped audit trail for orders, payments, stock movements, and roles.</p>
            </div>
            <a href="{{ route('platform.activity-logs.index', ['organization_id' => $organizationId]) }}" class="btn-secondary">Open full analysis</a>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total events</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($activitySummary['total'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Last 24 hours</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($activitySummary['last_24_hours'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Last 7 days</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($activitySummary['last_7_days'] ?? 0) }}</p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="form-label">Event</label>
                <select wire:model.live="activityFilters.event" class="form-input">
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>
            <div>
                <label class="form-label">Resource type</label>
                <select wire:model.live="activityFilters.subject_type" class="form-input">
                    <option value="">All types</option>
                    @foreach($activitySubjectTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-modern min-w-full">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>Resource</th>
                        <th>Actor</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityLogs as $item)
                        <tr wire:key="org-activity-{{ $item['id'] }}">
                            <td class="whitespace-nowrap text-sm text-slate-600">
                                {{ \Illuminate\Support\Carbon::parse($item['created_at'])->format('M j, Y g:i A') }}
                            </td>
                            <td><span class="badge badge-info capitalize">{{ $item['event'] ?? '—' }}</span></td>
                            <td class="text-sm text-slate-700">
                                <span class="font-medium">{{ $item['subject']['type'] ?? '—' }}</span>
                                @if(! empty($item['subject']['label']))
                                    <span class="block text-xs text-slate-500">{{ $item['subject']['label'] }}</span>
                                @endif
                            </td>
                            <td class="text-sm text-slate-700">{{ $item['causer']['name'] ?? 'System' }}</td>
                            <td class="max-w-xs text-xs text-slate-500">
                                @php $attributes = $item['changes']['attributes'] ?? null; @endphp
                                @if(is_array($attributes) && count($attributes) > 0)
                                    @foreach(collect($attributes)->take(2) as $field => $value)
                                        <div><span class="font-medium text-slate-600">{{ $field }}:</span> {{ is_scalar($value) ? $value : json_encode($value) }}</div>
                                    @endforeach
                                @else
                                    {{ $item['description'] ?? '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-slate-500">No activity recorded for this organization yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
