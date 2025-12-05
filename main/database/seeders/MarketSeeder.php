<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    public function run()
    {
        $markets = [
            'Forex',
            'Crypto',
            'Stocks',
            'Commodities',
            'Indices',
            'Futures',
            'Options'
        ];

        foreach ($markets as $market) {
            Market::firstOrCreate(
                ['name' => $market],
                ['status' => 1]
            );
        }
    }
}

