<?php

namespace Database\Seeders;

use App\Models\Signal;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class SignalAnalyticSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo signal analytics for reporting dashboard
     */
    public function run()
    {
        // Check if model exists
        if (!class_exists(\Addons\MultiChannelSignalAddon\App\Models\SignalAnalytic::class)) {
            $this->command->warn('SignalAnalytic model not found. Skipping seeder.');
            return;
        }

        $modelClass = \Addons\MultiChannelSignalAddon\App\Models\SignalAnalytic::class;

        // Get required data
        $signals = Signal::where('is_published', 1)->get();
        if ($signals->isEmpty()) {
            $this->command->warn('No published signals found. Skipping signal analytics seeding.');
            return;
        }

        $plans = Plan::where('status', 1)->get();
        $users = User::where('email', '!=', 'admin@admin.com')->take(10)->get();

        // Get channel sources if available
        $channelSourceClass = null;
        if (class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
            $channelSourceClass = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class;
        }
        $channelSources = $channelSourceClass ? $channelSourceClass::where('status', 'active')->get() : collect();

        $currencyPairs = ['EUR/USD', 'GBP/USD', 'USD/JPY', 'AUD/USD', 'BTC/USDT', 'ETH/USDT', 'XAU/USD'];
        $directions = ['buy', 'sell'];
        $tradeStatuses = ['closed', 'closed', 'closed', 'open', 'pending']; // 60% closed, 20% open, 20% pending

        $analytics = [];
        $totalAnalytics = 50;

        for ($i = 0; $i < $totalAnalytics; $i++) {
            $signal = $signals->random();
            $plan = $plans->isNotEmpty() ? $plans->random() : null;
            $user = $users->isNotEmpty() ? $users->random() : null;
            $channelSource = $channelSources->isNotEmpty() ? $channelSources->random() : null;

            $currencyPair = $currencyPairs[array_rand($currencyPairs)];
            $direction = $directions[array_rand($directions)];
            $tradeStatus = $tradeStatuses[array_rand($tradeStatuses)];

            // Generate realistic prices
            $basePrice = match(true) {
                str_contains($currencyPair, 'BTC') => rand(30000, 60000),
                str_contains($currencyPair, 'ETH') => rand(2000, 4000),
                str_contains($currencyPair, 'XAU') => rand(1800, 2200),
                default => rand(100, 200) / 100, // Forex pairs
            };

            $openPrice = $basePrice;
            $sl = $direction === 'buy' 
                ? $openPrice * (1 - rand(10, 50) / 1000) // 1-5% below
                : $openPrice * (1 + rand(10, 50) / 1000); // 1-5% above
            $tp = $direction === 'buy'
                ? $openPrice * (1 + rand(20, 100) / 1000) // 2-10% above
                : $openPrice * (1 - rand(20, 100) / 1000); // 2-10% below

            // Actual prices (for closed trades)
            $actualOpenPrice = $openPrice + ($openPrice * rand(-5, 5) / 1000); // Slight slippage
            $actualClosePrice = null;
            $profitLoss = 0;
            $pips = 0;

            if ($tradeStatus === 'closed') {
                // Closed trade - calculate actual close price and P&L
                $closePriceVariation = rand(-100, 150) / 1000; // -10% to +15% variation
                $actualClosePrice = $direction === 'buy'
                    ? $actualOpenPrice * (1 + $closePriceVariation)
                    : $actualOpenPrice * (1 - $closePriceVariation);

                $priceDiff = $direction === 'buy'
                    ? $actualClosePrice - $actualOpenPrice
                    : $actualOpenPrice - $actualClosePrice;

                // Calculate pips (for forex: 1 pip = 0.0001 for most pairs, 0.01 for JPY pairs)
                $pipValue = str_contains($currencyPair, 'JPY') ? 0.01 : 0.0001;
                $pips = abs($priceDiff / $pipValue);

                // Profit/Loss (simplified - in real scenario would use lot size)
                $lotSize = rand(1, 10) / 10; // 0.1 to 1.0 lots
                $profitLoss = $priceDiff * $lotSize * 100000; // Simplified calculation
            } elseif ($tradeStatus === 'open') {
                // Open trade - current price between entry and TP/SL
                $currentPrice = $direction === 'buy'
                    ? $actualOpenPrice + ($tp - $actualOpenPrice) * rand(20, 80) / 100
                    : $actualOpenPrice - ($actualOpenPrice - $tp) * rand(20, 80) / 100;
                $actualClosePrice = $currentPrice; // Current price (not closed yet)
            }

            // Timestamps
            $signalReceivedAt = now()->subDays(rand(1, 30))->subHours(rand(0, 23));
            $signalPublishedAt = $signalReceivedAt->copy()->addMinutes(rand(5, 30));
            $tradeOpenedAt = $tradeStatus !== 'pending' ? $signalPublishedAt->copy()->addMinutes(rand(1, 60)) : null;
            $tradeClosedAt = $tradeStatus === 'closed' && $tradeOpenedAt 
                ? $tradeOpenedAt->copy()->addHours(rand(1, 72)) 
                : null;

            $analyticData = [
                'signal_id' => $signal->id,
                'channel_source_id' => $channelSource?->id,
                'plan_id' => $plan?->id,
                'user_id' => $user?->id,
                'currency_pair' => $currencyPair,
                'direction' => $direction,
                'open_price' => $openPrice,
                'sl' => $sl,
                'tp' => $tp,
                'actual_open_price' => $actualOpenPrice,
                'actual_close_price' => $actualClosePrice,
                'profit_loss' => $profitLoss,
                'pips' => $pips,
                'trade_status' => $tradeStatus,
                'signal_received_at' => $signalReceivedAt,
                'signal_published_at' => $signalPublishedAt,
                'trade_opened_at' => $tradeOpenedAt,
                'trade_closed_at' => $tradeClosedAt,
                'metadata' => [
                    'parsing_confidence' => rand(70, 100),
                    'pattern_used' => ['regex', 'ai', 'template'][array_rand(['regex', 'ai', 'template'])],
                    'source_type' => $channelSource ? $channelSource->type : 'manual',
                ],
                'created_at' => $signalReceivedAt,
                'updated_at' => $tradeClosedAt ?? $tradeOpenedAt ?? $signalPublishedAt,
            ];

            $analytic = $modelClass::create($analyticData);
            $analytics[] = $analytic;
        }

        $this->command->info('Created ' . count($analytics) . ' signal analytics records successfully!');
    }
}
