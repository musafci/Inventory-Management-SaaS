<?php

namespace App\Console\Commands;

use App\Services\OrganizationSubscriptionService;
use Illuminate\Console\Command;

class ExpireTrialsCommand extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Mark past-due trial subscriptions as expired';

    public function handle(OrganizationSubscriptionService $subscriptionService): int
    {
        $expired = $subscriptionService->expireDueTrials();

        $this->info("Expired {$expired} trial subscription(s).");

        return self::SUCCESS;
    }
}
