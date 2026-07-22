<?php

namespace App\Console\Commands;

use App\Services\OrganizationDeletionService;
use Illuminate\Console\Command;

class ProcessOrganizationDeletionsCommand extends Command
{
    protected $signature = 'organizations:process-deletions';

    protected $description = 'Hard-delete organizations past their deletion grace period';

    public function handle(OrganizationDeletionService $deletionService): int
    {
        $deleted = $deletionService->processDueDeletions();

        $this->info("Deleted {$deleted} organization(s).");

        return self::SUCCESS;
    }
}
