<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant API rate limit
    |--------------------------------------------------------------------------
    |
    | Authenticated tenant routes are throttled per organization and user.
    | Auth routes (register/login) are not subject to this limiter.
    |
    */

    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),

    /*
    |--------------------------------------------------------------------------
    | Idempotency wait timeout
    |--------------------------------------------------------------------------
    |
    | When a duplicate Idempotency-Key arrives while the first request is still
    | processing, the second request polls until the first completes or this
    | timeout (seconds) elapses.
    |
    */

    'idempotency_wait_seconds' => (int) env('API_IDEMPOTENCY_WAIT_SECONDS', 30),

];
