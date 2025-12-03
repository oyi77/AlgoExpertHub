<?php

namespace Addons\AiConnectionAddon\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'OpenAI',
                'slug' => 'openai',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Google Gemini',
                'slug' => 'gemini',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'OpenRouter',
                'slug' => 'openrouter',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Check if providers already exist
        foreach ($providers as $provider) {
            $exists = DB::table('ai_providers')
                ->where('slug', $provider['slug'])
                ->exists();
            
            if (!$exists) {
                DB::table('ai_providers')->insert($provider);
            }
        }

        $this->command->info('Default AI providers seeded successfully!');
    }
}

