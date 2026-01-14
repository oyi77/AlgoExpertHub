<?php

namespace Addons\MultiChannelSignalAddon\App\Services\Gateway;

use App\Helpers\Helper\Helper;
use Addons\MultiChannelSignalAddon\App;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;

use Illuminate\Support\Facades\Log;

class PaypalService
{
    public function process($request, $paypal, $totalAmount, $deposit)
    {

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $paypal->parameter->paypal_client_id,
                $paypal->parameter->paypal_client_secret,

            )
        );

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // Set redirect URLs
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('user.paypal'))
            ->setCancelUrl(route('home'));


        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency($paypal->parameter->gateway_currency)
            ->setTotal($totalAmount);

        // Set transaction object
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription("Transaction Number {$deposit->trx}");

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));


        // Create payment with valid API context
        try {
            $payment->create($apiContext);

            // Get PayPal redirect URL and redirect the customer
            $approvalUrl = $payment->getApprovalLink();

            // Redirect the customer to $approvalUrl
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            Log::error('PayPal Connection Exception', ['code' => $ex->getCode(), 'data' => $ex->getData()]);
            return null;
        } catch (\Exception $ex) {
            Log::error('PayPal Exception', ['message' => $ex->getMessage()]);
            return null;
        }

        return $payment;
    }


    public function success()
    {
        $paypal = Gateway::where('name', 'paypal')->firstOrFail();

        if (session('type') == 'deposit') {
            $booking = Deposit::where('trx', session('trx'))->first();
        } else {
            $booking = ModelsPayment::where('trx', session('trx'))->first();
        }

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $paypal->parameter->paypal_client_id,
                $paypal->parameter->paypal_client_secret,
            )
        );

        // Get payment object by passing paymentId
        $paymentId = request()->input('paymentId');
        $payment = Payment::get($paymentId, $apiContext);
        $payerId = request()->input('PayerID');

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            // Execute payment
            $result = $payment->execute($execution, $apiContext);

            $transaction = $result->id;

            $transactionFee = json_decode($result)->transactions[0]->related_resources[0]->sale->transaction_fee->value / $paypal->rate;

            if ($result->state == 'approved') {

                Helper::paymentSuccess($booking, $transactionFee, $transaction);

                return ['type'=>'success', 'message'=>'Payment Successfully Done'];
            }
        } catch (\PayPal\Exception\PayPalConnectionException $ex) {
            Log::error('PayPal Connection Exception', ['code' => $ex->getCode(), 'data' => $ex->getData()]);
            return ['type' => 'error', 'message' => 'PayPal Connection Error'];
        } catch (\Exception $ex) {
            Log::error('PayPal Exception', ['message' => $ex->getMessage()]);
            return ['type' => 'error', 'message' => 'PayPal Error'];
        }
    }
}
