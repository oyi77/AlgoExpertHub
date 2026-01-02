<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Models\CurrencyPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSignalPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signals:fix-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix unrealistic signal prices in the database to make them look realistic';

    /**
     * Realistic price ranges for different currency pairs
     */
    private function getRealisticPriceRange(string $pairName): array
    {
        $pairUpper = strtoupper($pairName);
        
        // Crypto pairs (check BEFORE forex default)
        if (str_contains($pairUpper, 'BTC')) {
            return ['min' => 90000.00, 'max' => 98000.00];
        }
        if (str_contains($pairUpper, 'ETH')) {
            return ['min' => 3200.00, 'max' => 3600.00];
        }
        if (str_contains($pairUpper, 'BNB')) {
            return ['min' => 580.00, 'max' => 650.00];
        }
        if (str_contains($pairUpper, 'SOL')) {
            return ['min' => 180.00, 'max' => 220.00];
        }
        if (str_contains($pairUpper, 'ADA')) {
            return ['min' => 0.45, 'max' => 0.55];
        }
        if (str_contains($pairUpper, 'XRP')) {
            return ['min' => 0.55, 'max' => 0.65];
        }
        if (str_contains($pairUpper, 'DOT')) {
            return ['min' => 6.50, 'max' => 8.00];
        }
        
        // Stock Indices (check BEFORE forex default)
        if (str_contains($pairUpper, 'US500') || str_contains($pairUpper, 'SPX')) {
            return ['min' => 5800.00, 'max' => 6000.00];
        }
        if (str_contains($pairUpper, 'US30') || str_contains($pairUpper, 'DJI')) {
            return ['min' => 42000.00, 'max' => 44000.00];
        }
        if (str_contains($pairUpper, 'US100') || str_contains($pairUpper, 'NAS100') || str_contains($pairUpper, 'NDX')) {
            return ['min' => 18500.00, 'max' => 19500.00];
        }
        if (str_contains($pairUpper, 'UK100') || str_contains($pairUpper, 'FTSE')) {
            return ['min' => 8000.00, 'max' => 8500.00];
        }
        if (str_contains($pairUpper, 'GER40') || str_contains($pairUpper, 'DAX')) {
            return ['min' => 18000.00, 'max' => 19000.00];
        }
        if (str_contains($pairUpper, 'JPN225') || str_contains($pairUpper, 'NIKKEI')) {
            return ['min' => 38000.00, 'max' => 42000.00];
        }
        
        // Commodities
        if (str_contains($pairUpper, 'XAU') || str_contains($pairUpper, 'GOLD')) {
            return ['min' => 2500.00, 'max' => 2750.00];
        }
        if (str_contains($pairUpper, 'XAG') || str_contains($pairUpper, 'SILVER')) {
            return ['min' => 28.00, 'max' => 32.00];
        }
        if (str_contains($pairUpper, 'WTI') || str_contains($pairUpper, 'OIL') || str_contains($pairUpper, 'CRUDE')) {
            return ['min' => 70.00, 'max' => 85.00];
        }
        if (str_contains($pairUpper, 'BRENT')) {
            return ['min' => 75.00, 'max' => 90.00];
        }
        
        // Major Forex Pairs (typically 0.8 - 1.5 range)
        if (str_contains($pairUpper, 'EUR/GBP')) {
            return ['min' => 0.85, 'max' => 0.92];
        }
        if (str_contains($pairUpper, 'GBP/USD')) {
            return ['min' => 1.20, 'max' => 1.30];
        }
        if (str_contains($pairUpper, 'EUR/USD')) {
            return ['min' => 1.05, 'max' => 1.15];
        }
        if (str_contains($pairUpper, 'EUR/AUD')) {
            return ['min' => 1.55, 'max' => 1.65];
        }
        if (str_contains($pairUpper, 'EUR/JPY')) {
            return ['min' => 155.00, 'max' => 165.00];
        }
        if (str_contains($pairUpper, 'GBP/JPY')) {
            return ['min' => 185.00, 'max' => 195.00];
        }
        if (str_contains($pairUpper, 'USD/JPY')) {
            return ['min' => 148.00, 'max' => 155.00];
        }
        if (str_contains($pairUpper, 'AUD/USD')) {
            return ['min' => 0.65, 'max' => 0.72];
        }
        if (str_contains($pairUpper, 'USD/CAD')) {
            return ['min' => 1.32, 'max' => 1.38];
        }
        if (str_contains($pairUpper, 'NZD/USD')) {
            return ['min' => 0.60, 'max' => 0.67];
        }
        if (str_contains($pairUpper, 'USD/CHF')) {
            return ['min' => 0.88, 'max' => 0.95];
        }
        
        // JPY pairs (typically 100-200 range)
        if (str_contains($pairUpper, 'JPY')) {
            return ['min' => 140.00, 'max' => 160.00];
        }
        
        // Default: Major forex pairs (1.0 - 1.5 range)
        return ['min' => 1.05, 'max' => 1.15];
    }
    
    /**
     * Check if a price is unrealistic for a given pair
     */
    private function isUnrealisticPrice(float $price, string $pairName): bool
    {
        $range = $this->getRealisticPriceRange($pairName);
        
        // Price is unrealistic if it's way outside the expected range
        // Allow some flexibility (10x outside range is definitely wrong)
        $min = $range['min'];
        $max = $range['max'];
        
        // If price is more than 2x the max or less than 0.5x the min, it's unrealistic
        if ($price > ($max * 2) || $price < ($min * 0.5)) {
            return true;
        }
        
        // Also check for extremely high prices (likely decimal point errors)
        if ($price > 100 && $max < 10) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate a realistic price within the range
     */
    private function generateRealisticPrice(string $pairName): float
    {
        $range = $this->getRealisticPriceRange($pairName);
        $price = mt_rand((int)($range['min'] * 10000), (int)($range['max'] * 10000)) / 10000;
        
        // Round to appropriate decimal places
        if ($range['max'] < 10) {
            return round($price, 5); // 5 decimals for forex pairs
        } elseif ($range['max'] < 1000) {
            return round($price, 2); // 2 decimals for indices, gold, etc.
        } else {
            return round($price, 2); // 2 decimals for crypto
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix unrealistic signal prices...');
        
        $signals = Signal::with('pair')->get();
        $fixedCount = 0;
        $skippedCount = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($signals as $signal) {
                $pairName = $signal->pair->name ?? 'UNKNOWN';
                $openPrice = (float) $signal->open_price;
                $sl = (float) $signal->sl;
                $tp = (float) $signal->tp;
                
                // Check if any price is unrealistic
                $needsFix = $this->isUnrealisticPrice($openPrice, $pairName);
                
                if ($needsFix) {
                    $this->warn("Fixing signal ID {$signal->id} ({$pairName}): Entry was {$openPrice}");
                    
                    // Generate new realistic entry price
                    $newOpenPrice = $this->generateRealisticPrice($pairName);
                    
                    // Calculate new SL and TP based on direction and maintaining risk/reward
                    $direction = $signal->direction;
                    $slDistance = abs($openPrice - $sl);
                    $tpDistance = abs($tp - $openPrice);
                    $riskRewardRatio = $slDistance > 0 ? ($tpDistance / $slDistance) : 2.0;
                    
                    // Maintain similar risk/reward ratio but with realistic percentages
                    // For buy signals: SL below, TP above
                    // For sell signals: SL above, TP below
                    if (strtolower($direction) === 'buy' || strtolower($direction) === 'long') {
                        // 1-2% risk, 2-4% reward
                        $riskPercent = 0.01 + (mt_rand(0, 10) / 1000); // 1% - 1.1%
                        $rewardPercent = $riskPercent * min($riskRewardRatio, 3.0); // Maintain R:R but cap at 3:1
                        
                        $newSl = $newOpenPrice * (1 - $riskPercent);
                        $newTp = $newOpenPrice * (1 + $rewardPercent);
                    } else {
                        // Sell/Short
                        $riskPercent = 0.01 + (mt_rand(0, 10) / 1000);
                        $rewardPercent = $riskPercent * min($riskRewardRatio, 3.0);
                        
                        $newSl = $newOpenPrice * (1 + $riskPercent);
                        $newTp = $newOpenPrice * (1 - $rewardPercent);
                    }
                    
                    // Round to appropriate decimals
                    $range = $this->getRealisticPriceRange($pairName);
                    if ($range['max'] < 10) {
                        $decimals = 5; // Forex pairs (0.85 - 1.5 range)
                    } elseif ($range['max'] < 100) {
                        $decimals = 2; // Small crypto, commodities (28-100 range)
                    } elseif ($range['max'] < 10000) {
                        $decimals = 2; // Crypto, indices (100-10000 range)
                    } else {
                        $decimals = 2; // Large indices (10000+ range)
                    }
                    
                    $newOpenPrice = round($newOpenPrice, $decimals);
                    $newSl = round($newSl, $decimals);
                    $newTp = round($newTp, $decimals);
                    
                    // Update signal
                    $signal->open_price = $newOpenPrice;
                    $signal->sl = $newSl;
                    $signal->tp = $newTp;
                    $signal->save();
                    
                    $this->info("  → Fixed to Entry: {$newOpenPrice}, SL: {$newSl}, TP: {$newTp}");
                    $fixedCount++;
                } else {
                    $skippedCount++;
                }
            }
            
            DB::commit();
            
            $this->info("\n✓ Successfully fixed {$fixedCount} signals");
            $this->info("✓ Skipped {$skippedCount} signals (already realistic)");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error fixing signals: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
