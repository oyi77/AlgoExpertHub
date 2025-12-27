<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Configuration;
use App\Models\Deposit;
use App\Models\Payment;
use CoinpaymentsAPI as GlobalCoinpaymentsAPI;
use Illuminate\Http\Request;

class CoinpaymentsService extends BaseAdapter
{
    /**
     * Process payment with Coinpayments.
     */
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
            return (new static())->error($transaction['error']);
        }

        return $transaction['result']['status_url'];
    }

    /**
     * Handle success callback from Coinpayments.
     */
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
            return $this->error('Transaction not found');
        }

        $this->handlePaymentSuccess($payment, (float)$payment->charge, $trx);

        return $this->success('Payment Successful');
    }
}
