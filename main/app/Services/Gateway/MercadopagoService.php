<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\Request;

class MercadopagoService extends BaseAdapter
{
    /**
     * Process payment with Mercadopago.
     */
    public static function process($request, $gateway, float $amount, $deposit): array
    {
        $sandbox = false;

        $url = "https://api.mercadopago.com/checkout/preferences?access_token=" . $gateway->parameter->access_token;
        $headers = [
            "Content-Type: application/json",
        ];
        
        $postParam = [
            'items' => [
                [
                    'id' => $deposit->transaction_id,
                    'title' => number_format($amount, 2) . ' ' . $gateway->parameter->gateway_currency,
                    'description' => "Plan Purchase",
                    'installment' => 1,
                    'quantity' => 1,
                    'currency_id' => $gateway->parameter->gateway_currency,
                    'unit_price' => round($amount, 2)
                ]
            ],
            'payer' => [
                'email' => $deposit->user->email ?? '',
            ],
            'back_urls' => [
                'success' => route('user.dashboard'),
                'pending' => '',
                'failure' => route('user.dashboard'),
            ],
            'notification_url' => route('user.payment.success', $gateway->name),
            'auto_return' => 'approved',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParam));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode((string)$result);

        if (isset($response->auto_return) && $response->auto_return === 'approved') {
            $redirectUrl = $sandbox ? $response->sandbox_init_point : $response->init_point;
            return (new static())->successResponse('Redirect to Mercadopago', ['redirect_url' => $redirectUrl]);
        }

        return (new static())->error('Invalid Request');
    }

    /**
     * Handle success callback from Mercadopago.
     */
    public function success(Request $request): array
    {
        $trx = session('trx');
        $type = session('type');

        if ($type === 'deposit') {
            $deposit = Deposit::where('trx', $trx)->first();
        } else {
            $deposit = Payment::where('trx', $trx)->first();
        }

        if (!$deposit) {
            return $this->error('Transaction not found');
        }

        $url = "https://api.mercadopago.com/v1/payments/" . $request['data']['id'] . "?access_token=" . $deposit->gateway->parameter->access_token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $paymentData = json_decode((string)$result);

        if (isset($paymentData->status) && $paymentData->status === 'approved') {
            $this->handlePaymentSuccess($deposit, 0.0, $deposit->trx);

            return $this->success('Payment Successful');
        }

        return $this->error('Payment verification failed');
    }
}
