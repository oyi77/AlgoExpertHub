<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = [
            ['name' => 'password_reset', 'subject' => 'Password Reset'],
            ['name' => 'payment_successfull', 'subject' => 'Payment Successful'],
            ['name' => 'payment_received', 'subject' => 'Payment Received'],
            ['name' => 'verify_email', 'subject' => 'Verify Email'],
            ['name' => 'payment_confirmed', 'subject' => 'Payment Confirmed'],
            ['name' => 'payment_rejected', 'subject' => 'Payment Rejected'],
            ['name' => 'withdraw_accepted', 'subject' => 'Withdrawal Accepted'],
            ['name' => 'withdraw_rejected', 'subject' => 'Withdrawal Rejected'],
            ['name' => 'refer_commission', 'subject' => 'Referral Commission'],
            ['name' => 'send_money', 'subject' => 'Money Sent'],
            ['name' => 'receive_money', 'subject' => 'Money Received'],
            ['name' => 'plan_subscription', 'subject' => 'Plan Subscription'],
            ['name' => 'Signal', 'subject' => 'New Signal Arrived']
        ];

        foreach ($templates as $template) {
            DB::table('templates')->updateOrInsert(
                ['name' => $template['name']],
                array_merge($template, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
