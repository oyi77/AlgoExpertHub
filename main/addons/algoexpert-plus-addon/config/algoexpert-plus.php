<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AlgoExpert++ Addon Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AlgoExpert++ addon modules
    |
    */

    'modules' => [
        'seo' => [
            'enabled' => env('ALGOEXPERT_PLUS_SEO_ENABLED', true),
        ],
        'queues' => [
            'enabled' => env('ALGOEXPERT_PLUS_QUEUES_ENABLED', true),
            'requires_redis' => true,
        ],
        'backup' => [
            'enabled' => env('ALGOEXPERT_PLUS_BACKUP_ENABLED', true),
        ],
        'health' => [
            'enabled' => env('ALGOEXPERT_PLUS_HEALTH_ENABLED', true),
        ],
        'i18n' => [
            'enabled' => env('ALGOEXPERT_PLUS_I18N_ENABLED', true),
        ],
        'ui_components' => [
            'enabled' => env('ALGOEXPERT_PLUS_UI_COMPONENTS_ENABLED', false),
        ],
        'ai_tools' => [
            'enabled' => env('ALGOEXPERT_PLUS_AI_TOOLS_ENABLED', false),
        ],
    ],
];
