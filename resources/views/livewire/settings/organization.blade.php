<div>
    <x-settings-nav active="organization" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Organization profile</h3>
                    <p class="mt-1 text-sm text-slate-500">Update how your business appears across the workspace.</p>
                </div>
                <span class="badge badge-info">Org Owner</span>
            </div>

            <form wire:submit.prevent="save" class="space-y-5">
                <div>
                    <label class="form-label">Organization name</label>
                    <input type="text" wire:model="form.name" class="form-input">
                    @error('form.name') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Contact email</label>
                    <input type="email" wire:model="form.email" class="form-input">
                    @error('form.email') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" wire:model="form.phone" class="form-input" placeholder="Optional">
                    @error('form.phone') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn-primary">Save changes</button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Account</h3>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Slug</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization['slug'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Plan</dt>
                        <dd class="mt-1">
                            <span class="badge badge-info">{{ ucfirst($organization['plan'] ?? 'trial') }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Status</dt>
                        <dd class="mt-1">
                            <span @class([
                                'badge',
                                'badge-success' => ($organization['status'] ?? '') === 'active',
                                'badge-warning' => ($organization['status'] ?? '') === 'trial',
                                'badge-danger' => ($organization['status'] ?? '') === 'suspended',
                                'badge-info' => ! in_array($organization['status'] ?? '', ['active', 'trial', 'suspended'], true),
                            ])>{{ ucfirst($organization['status'] ?? '—') }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Team size</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $organization['users_count'] ?? 0 }} members</dd>
                    </div>
                    @if(! empty($organization['trial_ends_at']))
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Trial ends</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ \Illuminate\Support\Carbon::parse($organization['trial_ends_at'])->toFormattedDateString() }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Your access</h3>
                <p class="mt-3 text-sm text-slate-600">
                    You are signed in as the organization owner and can manage profile settings and team members.
                </p>
                @if(\App\Support\OrganizationSession::canManageUsers())
                    <a href="/settings/team" class="mt-4 inline-flex text-sm font-semibold text-primary-600 hover:text-primary-500">
                        Manage team members →
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
