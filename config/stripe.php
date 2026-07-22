<?php

return [

    'key' => env('STRIPE_KEY', 'pk_test_dummy_replace_me'),

    'secret' => env('STRIPE_SECRET', 'sk_test_dummy_replace_me'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', 'whsec_dummy_replace_me'),

    'prices' => [
        'starter' => [
            'monthly' => env('STRIPE_STARTER_PRICE_MONTHLY', 'price_dummy_starter_monthly'),
            'yearly' => env('STRIPE_STARTER_PRICE_YEARLY', 'price_dummy_starter_yearly'),
        ],
        'growth' => [
            'monthly' => env('STRIPE_GROWTH_PRICE_MONTHLY', 'price_dummy_growth_monthly'),
            'yearly' => env('STRIPE_GROWTH_PRICE_YEARLY', 'price_dummy_growth_yearly'),
        ],
        'business' => [
            'monthly' => env('STRIPE_BUSINESS_PRICE_MONTHLY', 'price_dummy_business_monthly'),
            'yearly' => env('STRIPE_BUSINESS_PRICE_YEARLY', 'price_dummy_business_yearly'),
        ],
    ],

];
