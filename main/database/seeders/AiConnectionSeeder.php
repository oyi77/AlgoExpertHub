<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiConnectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo AI connections for AI connection addon
     */
    public function run()
    {
        // First ensure providers are seeded
        if (class_exists(\Addons\AiConnectionAddon\Database\Seeders\DefaultProvidersSeeder::class)) {
            $this->call(\Addons\AiConnectionAddon\Database\Seeders\DefaultProvidersSeeder::class);
        }

        if (!class_exists(\Addons\AiConnectionAddon\App\Models\AiConnection::class)) {
            $this->command->warn('AI Connection model not found. Skipping seeder.');
            return;
        }

        $modelClass = \Addons\AiConnectionAddon\App\Models\AiConnection::class;
        $providerClass = \Addons\AiConnectionAddon\App\Models\AiProvider::class;

        // Get providers
        $openaiProvider = $providerClass::where('slug', 'openai')->first();
        $geminiProvider = $providerClass::where('slug', 'gemini')->first();
        $openrouterProvider = $providerClass::where('slug', 'openrouter')->first();

        if (!$openaiProvider || !$geminiProvider) {
            $this->command->warn('AI Providers not found. Run DefaultProvidersSeeder first.');
            return;
        }

        $connections = [
            [
                'provider_id' => $openaiProvider->id,
                'name' => 'OpenAI GPT-4 Production',
                'credentials' => [
                    'api_key' => 'demo_openai_api_key',
                ],
                'settings' => [
                    'model' => 'gpt-4',
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ],
                'status' => 'active',
                'priority' => 1,
                'rate_limit_per_minute' => 10,
                'rate_limit_per_day' => 1000,
                'last_used_at' => now()->subMinutes(5),
                'last_error_at' => null,
                'error_count' => 0,
                'success_count' => 150,
            ],
            [
                'provider_id' => $openaiProvider->id,
                'name' => 'OpenAI GPT-3.5 Turbo (Fast)',
                'credentials' => [
                    'api_key' => 'demo_openai_api_key',
                ],
                'settings' => [
                    'model' => 'gpt-3.5-turbo',
                    'temperature' => 0.2,
                    'max_tokens' => 300,
                ],
                'status' => 'active',
                'priority' => 2,
                'rate_limit_per_minute' => 20,
                'rate_limit_per_day' => 2000,
                'last_used_at' => now()->subMinutes(2),
                'last_error_at' => null,
                'error_count' => 0,
                'success_count' => 500,
            ],
            [
                'provider_id' => $geminiProvider->id,
                'name' => 'Google Gemini Pro',
                'credentials' => [
                    'api_key' => 'demo_gemini_api_key',
                ],
                'settings' => [
                    'model' => 'gemini-pro',
                    'temperature' => 0.5,
                    'max_tokens' => 800,
                ],
                'status' => 'active',
                'priority' => 1,
                'rate_limit_per_minute' => 5,
                'rate_limit_per_day' => 500,
                'last_used_at' => now()->subMinutes(10),
                'last_error_at' => null,
                'error_count' => 0,
                'success_count' => 80,
            ],
            [
                'provider_id' => $openrouterProvider->id ?? $openaiProvider->id,
                'name' => 'OpenRouter Claude (Fallback)',
                'credentials' => [
                    'api_key' => 'demo_openrouter_api_key',
                ],
                'settings' => [
                    'model' => 'anthropic/claude-3-sonnet',
                    'temperature' => 0.4,
                    'max_tokens' => 600,
                ],
                'status' => 'active',
                'priority' => 3,
                'rate_limit_per_minute' => 8,
                'rate_limit_per_day' => 800,
                'last_used_at' => now()->subHours(1),
                'last_error_at' => null,
                'error_count' => 0,
                'success_count' => 25,
            ],
            [
                'provider_id' => $openaiProvider->id,
                'name' => 'OpenAI Backup (Error State)',
                'credentials' => [
                    'api_key' => 'invalid_key',
                ],
                'settings' => [
                    'model' => 'gpt-4',
                    'temperature' => 0.3,
                ],
                'status' => 'error',
                'priority' => 99,
                'rate_limit_per_minute' => 5,
                'rate_limit_per_day' => 200,
                'last_used_at' => now()->subDays(1),
                'last_error_at' => now()->subHours(2),
                'error_count' => 15,
                'success_count' => 5,
            ],
        ];

        foreach ($connections as $connection) {
            $existing = $modelClass::where('name', $connection['name'])->first();
            
            if (!$existing) {
                $conn = new $modelClass();
                $conn->fill($connection);
                $conn->save();
            }
        }

        $this->command->info('AI Connections seeded successfully!');
    }
}
