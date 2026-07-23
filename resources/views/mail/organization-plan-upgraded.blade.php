<x-mail::message>
# Organization plan upgraded

An organization has upgraded or activated a paid plan on {{ config('app.name') }}.

**Organization:** {{ $organization->name }}  
**Slug:** {{ $organization->slug }}  
**Organization email:** {{ $organization->email ?? '—' }}  
**Phone:** {{ $organization->phone ?? '—' }}

**Previous plan:** {{ $previousPlanSlug ? ucfirst($previousPlanSlug) : '—' }}  
**Previous status:** {{ $previousStatus ? ucfirst(str_replace('_', ' ', $previousStatus)) : '—' }}

**New plan:** {{ $subscription->plan->name ?? ucfirst($organization->plan) }}  
**New status:** {{ ucfirst($subscription->status->value) }}  
**Billing interval:** {{ $subscription->billing_interval ? ucfirst($subscription->billing_interval) : '—' }}  
**Current period ends:** {{ $subscription->current_period_ends_at?->format('M j, Y g:i A') ?? '—' }}

@if($owner)
**Owner:** {{ $owner->name }}  
**Owner email:** {{ $owner->email }}
@endif

<x-mail::subcopy>
Organization ID: {{ $organization->id }} · Upgraded at {{ now()->format('M j, Y g:i A T') }}
</x-mail::subcopy>
</x-mail::message>
