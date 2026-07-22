<x-mail::message>
# Payment failed

Hi,

We could not process the latest payment for **{{ $organization->name }}**.

Please update your billing details within {{ config('subscription.past_due_grace_days', 7) }} days to avoid losing write access.

<x-mail::button :url="url('/settings/billing')">
Update billing
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
