<?php

namespace Database\Seeders;

use App\Models\TimeFrame;
use Illuminate\Database\Seeder;

class TimeFrameSeeder extends Seeder
{
    public function run()
    {
        $frames = [
            '1M', '5M', '15M', '30M', '1H', '2H', '4H', '6H', '8H', '12H', '1D', '1W', '1MO'
        ];

        foreach ($frames as $frame) {
            TimeFrame::firstOrCreate(
                ['name' => $frame],
                ['status' => 1]
            );
        }
    }
}

