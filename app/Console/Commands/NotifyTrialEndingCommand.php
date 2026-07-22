<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingSoonMail;
use App\Models\OrganizationSubscription;
use App\Enums\SubscriptionStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyTrialEndingCommand extends Command
{
    protected $signature = 'subscriptions:notify-trial-ending';

    protected $description = 'Send trial-ending-soon emails to organizations nearing trial expiry';

    public function handle(): int
    {
        $reminderDays = (int) config('subscription.trial_ending_reminder_days', 3);
        $windowStart = now()->addDays($reminderDays)->startOfDay();
        $windowEnd = now()->addDays($reminderDays)->endOfDay();

        $count = 0;

        OrganizationSubscription::query()
            ->with('organization')
            ->where('status', SubscriptionStatus::Trial)
            ->whereNull('trial_reminder_sent_at')
            ->whereBetween('trial_ends_at', [$windowStart, $windowEnd])
            ->each(function (OrganizationSubscription $subscription) use (&$count, $reminderDays): void {
                $organization = $subscription->organization;

                if ($organization === null) {
                    return;
                }

                Mail::to($organization->email)->send(new TrialEndingSoonMail($organization, $reminderDays));

                $subscription->forceFill(['trial_reminder_sent_at' => now()])->save();
                $count++;
            });

        $this->info("Sent {$count} trial-ending reminder(s).");

        return self::SUCCESS;
    }
}
