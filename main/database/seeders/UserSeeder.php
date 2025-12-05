<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo users for investor presentations
     */
    public function run()
    {
        $demoUsers = [
            [
                'username' => 'investor_demo',
                'email' => 'investor@demo.com',
                'phone' => '+1234567890',
                'password' => Hash::make('demo123'),
                'balance' => 10000.00,
                'is_email_verified' => 1,
                'is_sms_verified' => 1,
                'is_kyc_verified' => 1,
                'status' => 1,
                'ref_id' => 0,
            ],
            [
                'username' => 'trader_pro',
                'email' => 'trader@demo.com',
                'phone' => '+1234567891',
                'password' => Hash::make('demo123'),
                'balance' => 5000.00,
                'is_email_verified' => 1,
                'is_sms_verified' => 1,
                'is_kyc_verified' => 1,
                'status' => 1,
                'ref_id' => 0,
            ],
            [
                'username' => 'premium_user',
                'email' => 'premium@demo.com',
                'phone' => '+1234567892',
                'password' => Hash::make('demo123'),
                'balance' => 2500.00,
                'is_email_verified' => 1,
                'is_sms_verified' => 0,
                'is_kyc_verified' => 1,
                'status' => 1,
                'ref_id' => 0,
            ],
            [
                'username' => 'basic_user',
                'email' => 'basic@demo.com',
                'phone' => '+1234567893',
                'password' => Hash::make('demo123'),
                'balance' => 1000.00,
                'is_email_verified' => 1,
                'is_sms_verified' => 0,
                'is_kyc_verified' => 0,
                'status' => 1,
                'ref_id' => 0,
            ],
            [
                'username' => 'new_user',
                'email' => 'new@demo.com',
                'phone' => '+1234567894',
                'password' => Hash::make('demo123'),
                'balance' => 500.00,
                'is_email_verified' => 0,
                'is_sms_verified' => 0,
                'is_kyc_verified' => 0,
                'status' => 1,
                'ref_id' => 0,
            ],
        ];

        foreach ($demoUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Demo users created successfully!');
    }
}
