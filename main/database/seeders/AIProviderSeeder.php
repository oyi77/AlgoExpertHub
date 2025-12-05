<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AIProviderSeeder extends Seeder
{
    public function run()
    {
        $providers = [
            [
                'name' => 'OpenAI',
                'slug' => 'openai',
                'status' => 'active',
                'default_connection_id' => null
            ],
            [
                'name' => 'Google Gemini',
                'slug' => 'gemini',
                'status' => 'active',
                'default_connection_id' => null
            ],
            [
                'name' => 'OpenRouter',
                'slug' => 'openrouter',
                'status' => 'active',
                'default_connection_id' => null
            ]
        ];

        foreach ($providers as $provider) {
            DB::table('ai_providers')->updateOrInsert(
                ['slug' => $provider['slug']],
                array_merge($provider, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}

