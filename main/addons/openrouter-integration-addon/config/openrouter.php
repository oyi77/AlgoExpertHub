<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Configuration
    |--------------------------------------------------------------------------
    |
    | API keys are configured per-configuration in the admin panel.
    | No API key required in .env file.
    |
    */

    'api_url' => env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1'),
    
    'models_endpoint' => '/models',
    
    'chat_endpoint' => '/chat/completions',
    
    'default_timeout' => 30,
    
    'default_temperature' => 0.3,
    
    'default_max_tokens' => 500,
    
    'cache_models_for' => 3600, // 1 hour in seconds
    
    // Optional: Default site URL and name for HTTP-Referer and X-Title headers
    // Can be overridden per configuration in admin panel
    'default_site_url' => env('OPENROUTER_SITE_URL', env('APP_URL')),
    
    'default_site_name' => env('OPENROUTER_SITE_NAME', env('APP_NAME')),
];

