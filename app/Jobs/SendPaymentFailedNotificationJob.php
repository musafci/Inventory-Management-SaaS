<?php

namespace App\Jobs;

use App\Mail\PaymentFailedMail;
use App\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPaymentFailedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $organizationId,
    ) {}

    public function handle(): void
    {
        $organization = Organization::query()->find($this->organizationId);

        if ($organization === null) {
            return;
        }

        Mail::to($organization->email)->send(new PaymentFailedMail($organization));
    }
}
