<x-mail::message>
# Your trial ends in {{ $daysRemaining }} days

Hi,

Your **{{ $organization->name }}** trial on {{ config('app.name') }} ends on {{ $organization->trial_ends_at?->format('M j, Y') }}.

Choose a plan before then to keep making changes without interruption.

<x-mail::button :url="url('/settings/billing')">
View billing
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
