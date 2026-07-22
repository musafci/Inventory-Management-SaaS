<?php

return [

    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 14),

    'trial_plan_slug' => env('SUBSCRIPTION_TRIAL_PLAN_SLUG', 'growth'),

];
