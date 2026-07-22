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
                <span class="badge badge-info">{{ ucfirst($organization['plan'] ?? 'trial') }} plan</span>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Tenant controls</h3>
                    <p class="mt-1 text-sm text-slate-500">Update subscription plan and platform access status.</p>
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

                <div>
                    <label class="form-label">Plan</label>
                    <input type="text" wire:model="form.plan" class="form-input" placeholder="trial, starter, pro, enterprise...">
                    @error('form.plan') <p class="form-error">{{ $message }}</p> @enderror
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
</div>
