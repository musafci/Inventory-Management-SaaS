<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewReports(User $user): bool
    {
        return $user->can('reports.view');
    }
}
