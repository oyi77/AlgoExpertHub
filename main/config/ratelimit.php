<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting thresholds for different types of requests.
    | Values are in requests per minute unless otherwise specified.
    |
    */

    'default' => env('RATE_LIMIT_DEFAULT', 60),

    'api' => env('RATE_LIMIT_API', 60),

    'login' => env('RATE_LIMIT_LOGIN', 5),

    'password' => env('RATE_LIMIT_PASSWORD', 3),

    'registration' => env('RATE_LIMIT_REGISTRATION', 3),

    'api_endpoints' => [
        'signals' => env('RATE_LIMIT_SIGNALS', 100),
        'trades' => env('RATE_LIMIT_TRADES', 50),
        'users' => env('RATE_LIMIT_USERS', 30),
        'analytics' => env('RATE_LIMIT_ANALYTICS', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Decay Time
    |--------------------------------------------------------------------------
    |
    | How long (in minutes) until the rate limiter counter resets.
    |
    */

    'decay_minutes' => env('RATE_LIMIT_DECAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Store Driver
    |--------------------------------------------------------------------------
    |
    | The cache store to use for rate limiting. Defaults to Redis for
    | optimal performance in distributed environments.
    |
    */

    'store' => env('RATE_LIMIT_STORE', 'redis'),

];
