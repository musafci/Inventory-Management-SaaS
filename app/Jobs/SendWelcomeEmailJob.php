<?php

namespace App\Jobs;

use App\Mail\WelcomeMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $organizationId,
        public int $ownerUserId,
    ) {}

    public function handle(): void
    {
        $organization = Organization::query()->find($this->organizationId);
        $owner = User::query()->find($this->ownerUserId);

        if ($organization === null || $owner === null) {
            return;
        }

        Mail::to($owner->email)->send(new WelcomeMail($organization, $owner));
    }
}
