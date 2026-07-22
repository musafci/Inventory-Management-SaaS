<?php

namespace App\Jobs;

use App\Mail\OrganizationRegisteredMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrganizationRegisteredNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $organizationId,
        public int $ownerUserId,
    ) {}

    public function handle(): void
    {
        $recipient = config('organization.registration_notification_email');

        if (! is_string($recipient) || $recipient === '') {
            return;
        }

        $organization = Organization::query()->find($this->organizationId);
        $owner = User::query()->find($this->ownerUserId);

        if ($organization === null || $owner === null) {
            return;
        }

        Mail::to($recipient)->send(new OrganizationRegisteredMail($organization, $owner));
    }
}
