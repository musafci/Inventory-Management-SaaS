<x-mail::message>
# Your data export is ready

The data export for **{{ $organizationName }}** is ready to download.

<x-mail::button :url="$downloadUrl">
Download export
</x-mail::button>

This link expires in 24 hours.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
