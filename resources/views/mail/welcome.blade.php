<x-mail::message>
# Welcome to {{ config('app.name') }}

Hi {{ $owner->name }},

Your organization **{{ $organization->name }}** is ready. You have a {{ config('subscription.trial_days', 14) }}-day trial on the Growth plan.

<x-mail::button :url="url('/login')">
Sign in
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
