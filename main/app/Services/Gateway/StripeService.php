<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Helpers\Helper\Helper;
use Stripe;

class StripeService extends BaseAdapter
{
    public static function process($request, $stripe, $payingAmount, $deposit)
    {
        Stripe\Stripe::setApiKey($stripe->parameter->stripe_client_secret);

        try {
            $payment = Stripe\Charge::create([
                "amount" => $payingAmount * 100,
                "currency" => $stripe->parameter->gateway_currency,
                "source" => $request->stripeToken,
                "description" => "{$deposit->transaction_id}"
            ]);

            $responseData = $payment->jsonSerialize();
            $transaction = $responseData['id'];

            $bal = \Stripe\BalanceTransaction::retrieve($responseData['balance_transaction']);
            $balJson = $bal->jsonSerialize();

            $fee_amount = number_format(($balJson['fee'] / 100), 4) /  $stripe->rate;

            if ($payment->status == 'succeeded') {
                (new static())->handlePaymentSuccess($deposit, (float)$fee_amount, $transaction);
                return (new static())->success('Payment Successfully Done');
            }
        } catch (\Exception $e) {
            Log::error('Stripe payment failed', ['error' => $e->getMessage()]);
            return (new static())->error('Something Goes Wrong');
        }

        return (new static())->error('Something Goes Wrong');
    }
}
