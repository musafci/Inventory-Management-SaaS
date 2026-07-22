<div>
    <x-settings-nav active="billing" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Subscription & billing</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        @if($needsUpgrade)
                            Choose a plan and billing interval to continue after your trial.
                        @else
                            View your subscription details and manage payment through Stripe.
                        @endif
                    </p>
                </div>
                <span class="badge badge-info">Org Owner</span>
            </div>

            @if($checkoutStatus === 'success')
                <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-800">
                    Payment received. Your subscription will activate shortly once Stripe confirms the payment.
                </p>
            @elseif($checkoutStatus === 'cancelled')
                <p class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800">
                    Checkout was cancelled. You can try again when you are ready.
                </p>
            @endif

            @error('billing')
                <p class="form-error mb-5">{{ $message }}</p>
            @enderror

            @if($needsUpgrade)
                <form wire:submit.prevent="checkout" class="space-y-5">
                    <div>
                        <label class="form-label">Plan</label>
                        <select wire:model.live="planSlug" class="form-input">
                            @foreach($availablePlans as $plan)
                                <option value="{{ $plan['slug'] }}">
                                    {{ $plan['name'] }}
                                    — ${{ number_format($plan['price_monthly'], 0) }}/mo
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-sm text-slate-500">Enterprise plans are quote-based — contact sales.</p>
                    </div>

                    <div>
                        <label class="form-label">Billing interval</label>
                        <select wire:model="interval" class="form-input">
                            @php
                                $selected = collect($availablePlans)->firstWhere('slug', $planSlug);
                            @endphp
                            @if($selected)
                                <option value="monthly">Monthly — ${{ number_format($selected['price_monthly'], 0) }} / month</option>
                                <option value="yearly">Yearly — ${{ number_format($selected['price_annual'], 0) }} / year</option>
                            @endif
                        </select>
                        <p class="mt-1.5 text-sm text-slate-500">Secure checkout is handled by Stripe. Cancel anytime from the billing portal.</p>
                    </div>

                    @unless($stripeConfigured)
                        <p class="text-sm text-amber-700">
                            Stripe is not fully configured yet. Set your API keys and price IDs in the environment to enable checkout.
                        </p>
                    @endunless

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="checkout">
                            <span wire:loading.remove wire:target="checkout">Continue to checkout</span>
                            <span wire:loading wire:target="checkout">Redirecting...</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="space-y-5">
                    <div>
                        <label class="form-label">Plan</label>
                        <input type="text" readonly value="{{ $planName }}" class="form-input bg-slate-50 text-slate-700">
                    </div>

                    <div>
                        <label class="form-label">Subscription status</label>
                        <input type="text" readonly value="{{ ucfirst(str_replace('_', ' ', $status)) }}" class="form-input bg-slate-50 text-slate-700">
                    </div>

                    <div>
                        <label class="form-label">Billing interval</label>
                        <input type="text" readonly value="{{ ucfirst($subscription['billing_interval'] ?? '—') }}" class="form-input bg-slate-50 text-slate-700">
                    </div>

                    @if(! empty($subscription['current_period_ends_at']))
                        <div>
                            <label class="form-label">Current period ends</label>
                            <input type="text" readonly value="{{ \Illuminate\Support\Carbon::parse($subscription['current_period_ends_at'])->toFormattedDateString() }}" class="form-input bg-slate-50 text-slate-700">
                        </div>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="button"
                                wire:click="manageSubscription"
                                wire:loading.attr="disabled"
                                wire:target="manageSubscription"
                                class="btn-primary">
                            <span wire:loading.remove wire:target="manageSubscription">Manage subscription</span>
                            <span wire:loading wire:target="manageSubscription">Opening portal...</span>
                        </button>
                    </div>
                </div>
            @endif
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
                            <span class="badge badge-info">{{ ucfirst($organization['plan'] ?? $currentPlanSlug) }}</span>
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
                <a href="/settings/organization" class="mt-4 inline-flex text-sm font-semibold text-primary-600 hover:text-primary-500">
                    Organization settings →
                </a>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Plan limits</h3>
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Warehouses</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $limits['max_warehouses'] ?? 'Unlimited' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Team members</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $limits['max_users'] ?? 'Unlimited' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Products</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $limits['max_products'] ?? 'Unlimited' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Orders per month</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $limits['max_orders_per_month'] ?? 'Unlimited' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Your access</h3>
                <p class="mt-3 text-sm text-slate-600">
                    You are signed in as the organization owner and can manage billing, profile settings, and team members.
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
