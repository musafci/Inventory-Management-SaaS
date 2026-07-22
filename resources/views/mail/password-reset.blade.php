<x-mail::message>
# Reset your password

Hi {{ $userName }},

We received a request to reset your password. Click the button below to choose a new one.

<x-mail::button :url="$resetUrl">
Reset password
</x-mail::button>

This link expires in {{ config('auth.passwords.users.expire', 60) }} minutes. If you did not request a reset, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
