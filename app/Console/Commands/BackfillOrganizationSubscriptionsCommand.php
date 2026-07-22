<?php

namespace App\Console\Commands;

use App\Services\OrganizationSubscriptionService;
use Illuminate\Console\Command;

class BackfillOrganizationSubscriptionsCommand extends Command
{
    protected $signature = 'platform:subscriptions:backfill';

    protected $description = 'Assign trial subscriptions to organizations missing a subscription row';

    public function handle(OrganizationSubscriptionService $subscriptionService): int
    {
        $count = $subscriptionService->backfillMissingSubscriptions();

        $this->info("Backfilled {$count} organization subscription(s).");

        return self::SUCCESS;
    }
}
