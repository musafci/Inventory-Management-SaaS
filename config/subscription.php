<?php

return [

    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 14),

    'trial_plan_slug' => env('SUBSCRIPTION_TRIAL_PLAN_SLUG', 'growth'),

    'trial_ending_reminder_days' => (int) env('SUBSCRIPTION_TRIAL_ENDING_REMINDER_DAYS', 3),

    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 7),

    'deletion_grace_days' => (int) env('ORGANIZATION_DELETION_GRACE_DAYS', 30),

];
