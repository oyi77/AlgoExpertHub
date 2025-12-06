<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AiUsageAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo AI usage analytics for trading operations dashboard
     */
    public function run()
    {
        // Check if model exists
        if (!class_exists(\Addons\AiConnectionAddon\App\Models\AiConnectionUsage::class)) {
            $this->command->warn('AiConnectionUsage model not found. Skipping seeder.');
            return;
        }

        $modelClass = \Addons\AiConnectionAddon\App\Models\AiConnectionUsage::class;

        // Get AI connections
        if (!class_exists(\Addons\AiConnectionAddon\App\Models\AiConnection::class)) {
            $this->command->warn('AiConnection model not found. Skipping seeder.');
            return;
        }

        $connections = \Addons\AiConnectionAddon\App\Models\AiConnection::all();
        if ($connections->isEmpty()) {
            $this->command->warn('No AI connections found. Skipping usage analytics seeding.');
            return;
        }

        $features = [
            'signal_parsing' => 40, // 40% of requests
            'market_analysis' => 30, // 30% of requests
            'translation' => 20, // 20% of requests
            'risk_assessment' => 5, // 5% of requests
            'sentiment_analysis' => 5, // 5% of requests
        ];

        $usageRecords = [];
        $totalRecords = 80;

        // Generate records over last 30 days (more recent = more records)
        for ($i = 0; $i < $totalRecords; $i++) {
            $connection = $connections->random();
            
            // More recent days have more records
            $daysAgo = $i < 20 ? rand(0, 3) : ($i < 50 ? rand(4, 14) : rand(15, 30));
            $createdAt = now()->subDays($daysAgo)
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59))
                ->subSeconds(rand(0, 59));

            // Select feature based on distribution
            $rand = rand(1, 100);
            $cumulative = 0;
            $selectedFeature = 'signal_parsing';
            foreach ($features as $feature => $percentage) {
                $cumulative += $percentage;
                if ($rand <= $cumulative) {
                    $selectedFeature = $feature;
                    break;
                }
            }

            // Success rate: 85-90%
            $success = rand(1, 100) <= 88;

            // Token usage based on feature
            $tokensUsed = match($selectedFeature) {
                'signal_parsing' => rand(500, 2000),
                'market_analysis' => rand(1000, 5000),
                'translation' => rand(200, 1000),
                'risk_assessment' => rand(800, 3000),
                'sentiment_analysis' => rand(600, 2500),
                default => rand(500, 2000),
            };

            // Cost calculation (rough estimate: $0.01 per 1000 tokens)
            $cost = ($tokensUsed / 1000) * 0.01 * rand(80, 120) / 100; // Add some variance
            $cost = max(0.001, min(0.1, $cost)); // Clamp between $0.001 and $0.1

            // Response time (ms)
            $responseTime = match($selectedFeature) {
                'signal_parsing' => rand(500, 2000),
                'market_analysis' => rand(1000, 4000),
                'translation' => rand(300, 1500),
                'risk_assessment' => rand(800, 3000),
                'sentiment_analysis' => rand(600, 2500),
                default => rand(500, 2000),
            };

            $errorMessage = $success ? null : match(rand(1, 3)) {
                1 => 'Rate limit exceeded',
                2 => 'Invalid API key',
                3 => 'Model timeout',
                default => 'Unknown error',
            };

            $usageData = [
                'connection_id' => $connection->id,
                'feature' => $selectedFeature,
                'tokens_used' => $tokensUsed,
                'cost' => $cost,
                'success' => $success,
                'response_time_ms' => $responseTime,
                'error_message' => $errorMessage,
                'created_at' => $createdAt,
            ];

            $modelClass::create($usageData);
            $usageRecords[] = $usageData;
        }

        $this->command->info('Created ' . count($usageRecords) . ' AI usage analytics records successfully!');
    }
}
