<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiModelProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo AI model profiles for market analysis
     */
    public function run()
    {
        // Check which model class exists
        $modelClass = null;
        if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
            $modelClass = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class;
        } elseif (class_exists(\Addons\AiTradingAddon\App\Models\AiModelProfile::class)) {
            $modelClass = \Addons\AiTradingAddon\App\Models\AiModelProfile::class;
        }

        if (!$modelClass) {
            $this->command->warn('AI Model Profile model not found. Skipping seeder.');
            return;
        }

        // Get AI connections if available
        $aiConnections = [];
        if (class_exists(\Addons\AiConnectionAddon\App\Models\AiConnection::class)) {
            $aiConnections = \Addons\AiConnectionAddon\App\Models\AiConnection::all();
        }

        $profiles = [
            [
                'name' => 'OpenAI GPT-4 Signal Confirmation',
                'description' => 'Professional signal confirmation using GPT-4. Analyzes market conditions and confirms signal safety before execution.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'ai_connection_id' => $aiConnections->where('name', 'like', '%OpenAI%')->first()->id ?? null,
                'provider' => 'openai',
                'model_name' => 'gpt-4',
                'api_key_ref' => null,
                'mode' => 'CONFIRM',
                'prompt_template' => 'Analyze the following trading signal and confirm if it aligns with current market conditions. Signal: {signal_data}. Market data: {market_data}.',
                'settings' => [
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ],
                'max_calls_per_minute' => 10,
                'max_calls_per_day' => 1000,
            ],
            [
                'name' => 'Gemini Pro Market Scanner',
                'description' => 'Advanced market scanning using Google Gemini Pro. Scans markets for trading opportunities and generates signals.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'ai_connection_id' => $aiConnections->where('name', 'like', '%Gemini%')->first()->id ?? null,
                'provider' => 'gemini',
                'model_name' => 'gemini-pro',
                'api_key_ref' => null,
                'mode' => 'SCAN',
                'prompt_template' => 'Scan the following market data and identify potential trading opportunities. Market: {market_data}.',
                'settings' => [
                    'temperature' => 0.5,
                    'max_tokens' => 800,
                ],
                'max_calls_per_minute' => 5,
                'max_calls_per_day' => 500,
            ],
            [
                'name' => 'GPT-3.5 Position Manager',
                'description' => 'AI-powered position management using GPT-3.5. Monitors open positions and suggests adjustments for trailing stops and break-even.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'ai_connection_id' => $aiConnections->where('name', 'like', '%OpenAI%')->first()->id ?? null,
                'provider' => 'openai',
                'model_name' => 'gpt-3.5-turbo',
                'api_key_ref' => null,
                'mode' => 'POSITION_MGMT',
                'prompt_template' => 'Analyze the following open position and suggest management actions. Position: {position_data}. Market: {market_data}.',
                'settings' => [
                    'temperature' => 0.2,
                    'max_tokens' => 300,
                ],
                'max_calls_per_minute' => 20,
                'max_calls_per_day' => 2000,
            ],
            [
                'name' => 'Conservative Signal Confirmation',
                'description' => 'Conservative AI confirmation with strict safety checks. Only confirms signals with high probability of success.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'ai_connection_id' => $aiConnections->first()->id ?? null,
                'provider' => 'openai',
                'model_name' => 'gpt-4',
                'api_key_ref' => null,
                'mode' => 'CONFIRM',
                'prompt_template' => 'Perform strict safety analysis. Only confirm if signal has >80% probability. Signal: {signal_data}.',
                'settings' => [
                    'temperature' => 0.1,
                    'max_tokens' => 400,
                ],
                'max_calls_per_minute' => 5,
                'max_calls_per_day' => 500,
            ],
            [
                'name' => 'Aggressive Market Scanner',
                'description' => 'Aggressive market scanner that identifies multiple trading opportunities. Generates signals for active trading.',
                'created_by_user_id' => null,
                'visibility' => 'PUBLIC_MARKETPLACE',
                'clonable' => true,
                'enabled' => true,
                'ai_connection_id' => $aiConnections->where('name', 'like', '%Gemini%')->first()->id ?? null,
                'provider' => 'gemini',
                'model_name' => 'gemini-pro',
                'api_key_ref' => null,
                'mode' => 'SCAN',
                'prompt_template' => 'Aggressively scan for trading opportunities. Identify all potential signals. Market: {market_data}.',
                'settings' => [
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ],
                'max_calls_per_minute' => 10,
                'max_calls_per_day' => 2000,
            ],
        ];

        foreach ($profiles as $profile) {
            $modelClass::firstOrCreate(
                ['name' => $profile['name']],
                $profile
            );
        }

        $this->command->info('AI Model Profiles seeded successfully!');
    }
}
