<x-mail::message>
# New organization registered

A new organization has signed up on {{ config('app.name') }}.

**Organization:** {{ $organization->name }}  
**Slug:** {{ $organization->slug }}  
**Organization email:** {{ $organization->email }}  
**Phone:** {{ $organization->phone ?? '—' }}  
**Plan:** {{ ucfirst($organization->plan) }}  
**Trial ends:** {{ $organization->trial_ends_at?->format('M j, Y g:i A') ?? '—' }}

**Owner:** {{ $owner->name }}  
**Owner email:** {{ $owner->email }}

<x-mail::subcopy>
Organization ID: {{ $organization->id }} · Registered at {{ $organization->created_at?->format('M j, Y g:i A T') }}
</x-mail::subcopy>
</x-mail::message>
