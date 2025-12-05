<?php

namespace Database\Seeders;

use App\Models\Referral;
use Illuminate\Database\Seeder;

class ReferralSeeder extends Seeder
{
    public function run()
    {
        $referrals = [
            [
                'type' => 'invest',
                'level' => ['Level 1', 'Level 2', 'Level 3'],
                'commission' => ['10', '5', '3'],
                'status' => 1
            ],
            [
                'type' => 'subscription',
                'level' => ['Level 1', 'Level 2'],
                'commission' => ['15', '10'],
                'status' => 1
            ]
        ];

        foreach ($referrals as $referralData) {
            Referral::firstOrCreate(
                ['type' => $referralData['type']],
                $referralData
            );
        }
    }
}

