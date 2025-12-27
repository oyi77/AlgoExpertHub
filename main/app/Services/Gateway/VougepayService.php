<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Payment;
use Illuminate\Http\Request;

class VougepayService extends BaseAdapter
{
    /**
     * Handle success callback from Vougepay.
     */
    public function success(Request $request): array
    {
        $request->validate([
            'transaction_id' => 'required'
        ]);

        $vogueUrl = "https://voguepay.com/?v_transaction_id={$request->transaction_id}&type=json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $vogueUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $vogueData = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string)$vogueData);
        
        if (!$data || !isset($data->merchant_ref)) {
            return $this->error('Invalid response from Vougepay');
        }

        $transactionId = $data->merchant_ref;
        $deposit = Payment::where('trx', $transactionId)->first();

        if (!$deposit) {
            return $this->error('Transaction not found');
        }

        if ($data->status === "Approved") {
            $this->handlePaymentSuccess($deposit, (float)$deposit->charge, (string)$request->transaction_id);

            return $this->success('Payment Successful');
        }

        return $this->error('Payment not approved');
    }
}
