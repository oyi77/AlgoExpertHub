<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gateways = ['stripe', 'paypal', 'vougepay', 'razorpay', 'coinpayments', 'mollie', 'nowpayments', 'flutterwave', 'paystack', 'paghiper', 'gourl_BTC', 'perfectmoney', 'mercadopago', 'paytm'];
        
        foreach ($gateways as $gateway) {
            DB::table('gateways')->updateOrInsert(
                ['name' => $gateway],
                [
                    'name' => $gateway,
                    'type' => 1,
                    'status' => 1,
                    'rate' => 1.00000000,
                    'charge' => 0.00000000,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
