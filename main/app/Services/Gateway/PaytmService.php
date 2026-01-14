<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaytmService extends BaseAdapter
{
    private const SANDBOX_ENDPOINT = 'https://securegw-stage.paytm.in/theia/processTransaction';
    private const PRODUCTION_ENDPOINT = 'https://securegw.paytm.in/theia/processTransaction';

    /**
     * Process payment with Paytm.
     */
    public static function process($request, $paytm, float $totalAmount, $deposit): array
    {
        $paytmParams = [
            'MID' => trim($paytm->parameter->merchant_id),
            'WEBSITE' => trim($paytm->parameter->merchant_website),
            'CHANNEL_ID' => trim($paytm->parameter->merchant_channel),
            'INDUSTRY_TYPE_ID' => trim($paytm->parameter->merchant_industry),
            'ORDER_ID' => $deposit->trx,
            'TXN_AMOUNT' => round($totalAmount, 2),
            'CUST_ID' => (string)$deposit->user_id,
            'CALLBACK_URL' => route('user.payment.success', $paytm->name),
        ];

        $paytmParams['CHECKSUMHASH'] = (new paytmCheckSum())->getChecksumFromArray($paytmParams, $paytm->parameter->merchant_key);

        $response = [
            'paytm_params' => $paytmParams,
            'redirect_url' => $paytm->parameter->mode ? self::PRODUCTION_ENDPOINT : self::SANDBOX_ENDPOINT
        ];

        return (new static())->successResponse('Redirect to Paytm', ['data' => $response]);
    }

    /**
     * Handle success callback from Paytm.
     */
    public function success(Request $request): array
    {
        $orderId = $request->input('ORDERID');
        $type = session('type');

        if ($type === 'deposit') {
            $payment = Deposit::where('trx', $orderId)->first();
        } else {
            $payment = Payment::where('trx', $orderId)->first();
        }

        if (!$payment) {
            return $this->returnError('Transaction not found');
        }

        $ptm = new paytmCheckSum();
        $paramList = $request->all();
        $checksumHash = $request->input('CHECKSUMHASH', '');
        
        $isValidChecksum = $ptm->verifychecksum_e($paramList, $payment->gateway->parameter->merchant_key, $checksumHash);

        if ($isValidChecksum === "TRUE") {
            if ($request->input('STATUS') === "TXN_SUCCESS") {
                $this->handlePaymentSuccess($payment, 0.0, $request->input('BANKTXNID', ''));

                return $this->returnSuccess('Payment Successful');
            }
            
            return $this->returnError('Payment Unsuccessful');
        }

        return $this->returnError('Checksum verification failed');
    }
}
