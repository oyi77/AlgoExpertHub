<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Configuration;

class NowpaymentsService extends BaseAdapter
{
    public static function process($request, $gateway, $total, $deposit)
    {
        $client = new NowPaymentsAPI($gateway->parameter->nowpay_key);
        $general = Configuration::first();

        $payment = $client->createInvoice([
            "price_amount" => $total,
            "price_currency" => $gateway->parameter->gateway_currency,
            "pay_currency" => "btc",
            "ipn_callback_url" => "https://nowpayments.io",
            "order_id" => $deposit->trx,
            "order_description" => "Plan Purchase",
            'success_url' => route('user.payment.success', $gateway->name), 
            'cancel_url' => route('user.payment.success', $gateway->name)
        ]);

        $data = json_decode($payment);

        if ($data) {
            return (new static())->returnSuccess('Payment link created', ['invoice' => $data]);
        }

        return (new static())->returnError('Something Goes Wrong');
    }
}
