<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'price' => 0,
                'duration' => 7,
                'plan_type' => 'limited',
                'price_type' => 'free',
                'feature' => ['Basic signals', '7 days access', 'Email support'],
                'whatsapp' => 0,
                'telegram' => 1,
                'email' => 1,
                'sms' => 0,
                'dashboard' => 1,
                'status' => 1
            ],
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'price' => 29.99,
                'duration' => 30,
                'plan_type' => 'limited',
                'price_type' => 'paid',
                'feature' => ['All signals', 'Telegram notifications', 'Email alerts', 'Dashboard access'],
                'whatsapp' => 0,
                'telegram' => 1,
                'email' => 1,
                'sms' => 0,
                'dashboard' => 1,
                'status' => 1
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro-monthly',
                'price' => 49.99,
                'duration' => 30,
                'plan_type' => 'limited',
                'price_type' => 'paid',
                'feature' => ['All signals', 'Priority support', 'Telegram + WhatsApp', 'Auto-trading integration', 'Advanced analytics'],
                'whatsapp' => 1,
                'telegram' => 1,
                'email' => 1,
                'sms' => 0,
                'dashboard' => 1,
                'status' => 1
            ],
            [
                'name' => 'Premium Yearly',
                'slug' => 'premium-yearly',
                'price' => 499.99,
                'duration' => 365,
                'plan_type' => 'limited',
                'price_type' => 'paid',
                'feature' => ['All signals', 'VIP support', 'All channels (Telegram, WhatsApp, Email, SMS)', 'Auto-trading', 'Copy trading', 'Custom presets', '2 months free'],
                'whatsapp' => 1,
                'telegram' => 1,
                'email' => 1,
                'sms' => 1,
                'dashboard' => 1,
                'status' => 1
            ],
            [
                'name' => 'Lifetime',
                'slug' => 'lifetime',
                'price' => 999.99,
                'duration' => 0,
                'plan_type' => 'unlimited',
                'price_type' => 'paid',
                'feature' => ['Lifetime access', 'All features', 'Priority VIP support', 'All notification channels', 'Auto-trading unlimited', 'Copy trading', 'Early access to new features'],
                'whatsapp' => 1,
                'telegram' => 1,
                'email' => 1,
                'sms' => 1,
                'dashboard' => 1,
                'status' => 1
            ]
        ];

        foreach ($plans as $planData) {
            Plan::firstOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}

