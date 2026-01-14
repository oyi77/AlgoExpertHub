<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Helpers\Helper\Helper;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Payment;
use App\Services\WebhookVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                return (new static())->returnSuccess('Payment Successfully Done');
            }
        } catch (\Exception $e) {
            Log::error('Stripe payment failed', ['error' => $e->getMessage()]);
            return (new static())->returnError('Something Goes Wrong');
        }

        return (new static())->returnError('Something Goes Wrong');
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

        $gateway = Gateway::where('status', 1)->where('name', 'stripe')->first();

        if (!$gateway || !$gateway->parameter?->stripe_webhook_secret) {
            Log::warning('Stripe webhook called but no secret configured', [
                'trx' => $trx,
            ]);
            $this->handlePaymentSuccess($payment, (float)$payment->charge, $trx);
            return $this->returnSuccess('Payment Successful');
        }

        $verification = WebhookVerificationService::verifyStripe(
            $request,
            $gateway->parameter->stripe_webhook_secret
        );

        if (!$verification['valid']) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $verification['error'],
                'trx' => $trx,
            ]);
            return $this->returnError('Invalid webhook signature');
        }

        $this->handlePaymentSuccess($payment, (float)$payment->charge, $trx);

        return $this->returnSuccess('Payment Successful');
    }
}
