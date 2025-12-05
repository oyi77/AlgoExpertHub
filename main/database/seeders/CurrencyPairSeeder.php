<?php

namespace Database\Seeders;

use App\Models\CurrencyPair;
use Illuminate\Database\Seeder;

class CurrencyPairSeeder extends Seeder
{
    public function run()
    {
        $pairs = [
            // Forex Major Pairs
            'EUR/USD', 'GBP/USD', 'USD/JPY', 'AUD/USD', 'USD/CHF', 'USD/CAD', 'NZD/USD',
            // Forex Cross Pairs
            'EUR/GBP', 'EUR/JPY', 'GBP/JPY', 'AUD/JPY', 'EUR/AUD',
            // Crypto
            'BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'XRP/USDT', 'ADA/USDT', 'SOL/USDT', 'DOT/USDT',
            // Commodities
            'XAU/USD', 'XAG/USD', 'WTI/USD', 'BRENT/USD',
            // Indices
            'US30', 'US100', 'US500', 'UK100', 'GER40', 'JPN225'
        ];

        foreach ($pairs as $pair) {
            CurrencyPair::firstOrCreate(
                ['name' => $pair],
                ['status' => 1]
            );
        }
    }
}

