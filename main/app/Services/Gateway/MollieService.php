<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Mollie\Laravel\Facades\Mollie as FacadesMollie;

class MollieService extends BaseAdapter
{
    /**
     * Process payment with Mollie.
     */
    public static function process($request, $gateway, $amount, $deposit): array
    {
        FacadesMollie::api()->setApiKey($gateway->parameter->mollie_key);

        try {
            $payment = FacadesMollie::api()->payments->create([
                "amount" => [
                    "currency" => $gateway->parameter->gateway_currency,
                    "value" => sprintf('%0.2f', round((float)$amount, 2)),
                ],
                'description' => "Payment For Purchasing Plan",
                "redirectUrl" => route('user.payment.success', $gateway->name),
                'metadata' => [
                    "order_id" => $deposit->trx,
                ],
            ]);
        } catch (\Throwable $th) {
            return (new static())->returnError('Something went wrong! Check your API credentials');
        }

        session()->put('payment_id', $payment->id);
        session()->put('trx', $deposit->trx);

        return ['redirect_url' => $payment->getCheckoutUrl()];
    }

    /**
     * Handle success callback from Mollie.
     */
    public static function success(): array
    {
        $trx = session('trx');
        $type = session('type');
        $paymentId = session('payment_id');

        if ($type === 'deposit') {
            $deposit = Deposit::where('trx', $trx)->first();
        } else {
            $deposit = Payment::where('trx', $trx)->first();
        }

        if (!$deposit) {
            return (new static())->returnError('Transaction not found');
        }

        FacadesMollie::api()->setApiKey($deposit->gateway->gateway_parameters->mollie_key);

        try {
            $payment = FacadesMollie::api()->payments()->get($paymentId);

            if ($payment->isPaid()) {
                (new static())->handlePaymentSuccess($deposit, (float)$deposit->charge, $deposit->transaction_id);

                return (new static())->returnSuccess('Payment Successful');
            }
        } catch (\Throwable $e) {
            return (new static())->returnError('Mollie API error: ' . $e->getMessage());
        }

        return (new static())->returnError('Something Went Wrong');
    }
}
