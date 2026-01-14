<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Helpers\Helper\Helper;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Payment as ModelsPayment;
use App\Services\WebhookVerificationService;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaypalService extends BaseAdapter
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

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(route('user.paypal'))
            ->setCancelUrl(route('home'));

        $amount = new Amount();
        $amount->setCurrency($paypal->parameter->gateway_currency)
            ->setTotal($totalAmount);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription("Transaction Number {$deposit->trx}");

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($apiContext);
            $approvalUrl = $payment->getApprovalLink();
        } catch (Exception $ex) {
            $this->log('Paypal payment creation failed', ['error' => $ex->getMessage()]);
            return null; // Paypal redirect logic seems to expect the payment object or it handles it elsewhere
        }

        return $payment;
    }

    public function success(Request $request): array
    {
        $paypal = Gateway::where('name', 'paypal')->firstOrFail();

        // $request is passed from controller
        $verification = WebhookVerificationService::verifyPayPal(
            $request,
            $paypal->parameter->paypal_webhook_id ?? '',
            $paypal->parameter->paypal_webhook_secret ?? ''
        );

        if (!$verification['valid']) {
            Log::warning('PayPal webhook signature verification failed', [
                'error' => $verification['error'],
                'trx' => session('trx'),
            ]);
            return $this->returnError('Invalid webhook signature');
        }

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

        $paymentId = $request->input('paymentId', '');
        $payment = Payment::get($paymentId, $apiContext);
        $payerId = $request->input('PayerID', '');

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            $result = $payment->execute($execution, $apiContext);
            $transactionId = $result->id;
            $transactionFee = json_decode($result->toJSON())->transactions[0]->related_resources[0]->sale->transaction_fee->value / $paypal->rate;

            if ($result->getState() == 'approved') {
                $this->handlePaymentSuccess($booking, (float)$transactionFee, $transactionId);
                return $this->returnSuccess('Payment Successfully Done');
            }
        } catch (Exception $ex) {
            $this->log('Paypal payment success execution failed', ['error' => $ex->getMessage()]);
            return $this->returnError('Something Goes Wrong');
        }

        return $this->returnError('Something Goes Wrong');
    }
}
