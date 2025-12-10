<?php

return [
    'title' => config('app.name') . ' API Documentation',
    'description' => '',
    'intro_text' => <<<INTRO
        This documentation aims to provide all the information you need to work with our API.

        <aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
        You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>
    INTRO,
    'base_url' => config('app.url'),
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => ['*/sanctum/csrf-cookie'],
        ],
    ],
    'type' => 'laravel',
    'theme' => 'default',
    'static' => [
        'output_path' => 'public/docs',
    ],
    'laravel' => [
        'add_routes' => false,
        'docs_url' => '/docs',
        'assets_directory' => null,
        'middleware' => [],
    ],
    'external' => [
        'html_attributes' => [],
    ],
    'try_it_out' => [
        'enabled' => true,
        'base_url' => null,
        'use_csrf' => true,
        'csrf_url' => '/sanctum/csrf-cookie',
    ],
    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => 'Bearer {TOKEN}',
        'extra_info' => 'Set the Authorization header to <code>Bearer {TOKEN}</code>. If you are using Sanctum, obtain a token and ensure CORS and CSRF are configured.',
    ],
    'example_languages' => ['bash', 'javascript', 'php', 'python'],
    'postman' => [
        'enabled' => true,
        'overrides' => [],
    ],
    'openapi' => [
        'enabled' => true,
        'version' => '3.0.3',
        'overrides' => [],
        'generators' => [],
    ],
    'groups' => [
        'default' => 'Endpoints',
        'order' => [
            'Authentication',
            'User APIs',
            'Admin APIs',
            'Webhooks',
            'Trading Management',
            'AI Connection',
            'Page Builder',
            'Multi-Channel Signal',
        ],
    ],
    'logo' => false,
    'last_updated' => 'Last updated: {date:F j, Y}',
    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],
    'database_connections_to_transact' => [config('database.default')],
    'fractal' => ['serializer' => null],
];
