<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaystackService extends BaseAdapter
{
    /**
     * Handle success callback from Paystack.
     * 
     * @param Request $request
     * @return array
     */
    public function success(Request $request): array
    {
        if (isset($request['reference'])) {
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

            $this->handlePaymentSuccess($payment, (float)$payment->charge, $request['reference']);

            return $this->success('Payment Successfully received');
        }

        return $this->error('Invalid reference');
    }
}
