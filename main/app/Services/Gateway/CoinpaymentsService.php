<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Helpers\Helper\Helper;
use App\Models\Deposit;
use App\Models\Payment;
use App\Services\WebhookVerificationService;
use CoinpaymentsAPI as GlobalCoinpaymentsAPI;
use Illuminate\Http\Request;

class CoinpaymentsService extends BaseAdapter
{
    public static function process($request, $gateway, float $totalAmount, $deposit): array|string
    {
        $amount = $totalAmount;
        $currency = 'USD';
        $referenceCode = $deposit->trx;

        $api = new GlobalCoinpaymentsAPI($gateway->parameter->private_key, $gateway->parameter->public_key, '');
        $req = [
            'amount' => $amount,
            'currency1' => $gateway->parameter->gateway_currency,
            'currency2' => $currency,
            'item_name' => 'Payment for Order #' . $referenceCode,
            'invoice' => $referenceCode,
            'buyer_email' => $deposit->user->email,
            'ipn_url' => url('/coinpayments/ipn'),
            'success_url' => url('/payment/success'),
            'cancel_url' => url('/payment/cancel'),
        ];

        $transaction = $api->CreateCustomTransaction($req);

        if (isset($transaction['error'])) {
            return (new static())->returnError($transaction['error']);
        }

        return $transaction['result']['status_url'];
    }

    public function success(Request $request): array
    {
        $trx = session('trx');
        $type = session('type');

        if ($type === 'deposit') {
            $payment = Deposit::where('trx', $trx)->first();
        } else {
            $payment = Payment::where('trx', $trx)->first();
        }

        if (!$payment) {
            return $this->returnError('Transaction not found');
        }

        $gateway = \App\Models\Gateway::where('status', 1)->where('name', 'coinpayments')->first();

        if (!$gateway || !$gateway->parameter?->ipn_secret) {
            Log::warning('Coinpayments webhook called but no secret configured', [
                'trx' => $trx,
            ]);
            $this->handlePaymentSuccess($payment, (float)$payment->charge, $trx);
            return $this->returnSuccess('Payment Successful');
        }

        $verification = WebhookVerificationService::verifyCoinpayments(
            $request,
            $gateway->parameter->ipn_secret
        );

        if (!$verification['valid']) {
            Log::warning('Coinpayments webhook signature verification failed', [
                'error' => $verification['error'],
                'trx' => $trx,
            ]);
            return $this->returnError('Invalid webhook signature');
        }

        $this->handlePaymentSuccess($payment, (float)$payment->charge, $trx);

        return $this->returnSuccess('Payment Successful');
    }
}
