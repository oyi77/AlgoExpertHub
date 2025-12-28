<?php

namespace Database\Seeders;

use App\Models\Signal;
use App\Models\CurrencyPair;
use App\Models\TimeFrame;
use App\Models\Market;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SignalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo trading signals for investor presentations
     */
    public function run()
    {
        // Get required data
        $pairs = CurrencyPair::all();
        $timeframes = TimeFrame::all();
        $markets = Market::all();
        $plans = Plan::all();

        if ($pairs->isEmpty() || $timeframes->isEmpty() || $markets->isEmpty()) {
            $this->command->warn('Currency pairs, timeframes, or markets not found. Skipping signal seeding.');
            return;
        }

        // Clear existing demo signals (optional - comment out if you want to keep existing)
        // Signal::where('auto_created', 1)->orWhere('title', 'like', '%demo%')->delete();

        $directions = ['buy', 'sell'];
        $signals = [];
        $targetCount = 50;
        $existingCount = Signal::count();

        // Only create if we don't have enough signals
        if ($existingCount >= $targetCount) {
            $this->command->info("Already have {$existingCount} signals. Skipping signal seeding.");
            return;
        }

        $toCreate = $targetCount - $existingCount;

        // Create demo signals with various statuses
        for ($i = 0; $i < $toCreate; $i++) {
            $pair = $pairs->random();
            $timeframe = $timeframes->random();
            $market = $markets->random();
            $direction = $directions[array_rand($directions)];
            
            // Generate realistic prices
            // Generate realistic prices
            $basePrice = match(true) {
                str_contains($pair->name, 'JPY') => rand(14000, 16000) / 100, // 140.00 - 160.00
                str_contains($pair->name, 'XAU') || str_contains($pair->name, 'GOLD') => rand(250000, 275000) / 100, // 2500.00 - 2750.00
                str_contains($pair->name, 'BTC') => rand(9000000, 9800000) / 100, // 90000.00 - 98000.00
                str_contains($pair->name, 'ETH') => rand(320000, 360000) / 100, // 3200.00 - 3600.00
                str_contains($pair->name, 'US500') || str_contains($pair->name, 'SPX') => rand(580000, 600000) / 100, // 5800.00 - 6000.00
                str_contains($pair->name, 'US30') || str_contains($pair->name, 'DJI') => rand(4200000, 4400000) / 100, // 42000.00 - 44000.00
                default => rand(10500, 11500) / 10000, // 1.0500 - 1.1500 (EUR/USD, GBP/USD range)
            };

            $openPrice = $basePrice;
            $sl = $direction === 'buy' 
                ? $basePrice * (1 - rand(5, 20) / 1000) // 0.5-2% below for buy
                : $basePrice * (1 + rand(5, 20) / 1000); // 0.5-2% above for sell
            $tp = $direction === 'buy'
                ? $basePrice * (1 + rand(10, 40) / 1000) // 1-4% above for buy
                : $basePrice * (1 - rand(10, 40) / 1000); // 1-4% below for sell

            $isPublished = rand(0, 10) > 2; // 80% published
            // published_date is NOT NULL, so always set a value
            // For drafts, use current time; for published, use past date
            $publishedDate = $isPublished 
                ? Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))
                : Carbon::now();

            $signal = Signal::create([
                'title' => "{$pair->name} {$direction} signal - " . strtoupper($timeframe->name),
                'currency_pair_id' => $pair->id,
                'time_frame_id' => $timeframe->id,
                'market_id' => $market->id,
                'open_price' => $openPrice,
                'sl' => $sl,
                'tp' => $tp,
                'direction' => $direction,
                'description' => "Demo trading signal for {$pair->name} on {$timeframe->name} timeframe. This is a {$direction} signal with entry at {$openPrice}, stop loss at {$sl}, and take profit at {$tp}.",
                'is_published' => $isPublished ? 1 : 0,
                'published_date' => $publishedDate,
                'status' => 1,
                'auto_created' => rand(0, 1),
            ]);

            // Assign to random plans
            if ($plans->isNotEmpty() && $isPublished) {
                $selectedPlans = $plans->random(rand(1, min(3, $plans->count())));
                $signal->plans()->attach($selectedPlans->pluck('id'));
            }

            $signals[] = $signal;
        }

        $this->command->info('Created ' . count($signals) . ' demo signals successfully!');
    }
}
