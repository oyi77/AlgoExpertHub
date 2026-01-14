<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\Request;

class RazorpayService extends BaseAdapter
{
    /**
     * Handle success callback from Razorpay.
     * 
     * @param Request $request
     * @return array
     */
    public static function success(Request $request): array
    {
        $data = $request->all();
        $trx = session('trx');
        $type = session('type');

        if ($type === 'deposit') {
            $deposit = Deposit::where('trx', $trx)->first();
        } else {
            $deposit = Payment::where('trx', $trx)->first();
        }

        if (!$deposit) {
            return (new static())->returnError('Transaction not found');
        }

        if (isset($data['razorpay_payment_id'])) {
            (new static())->handlePaymentSuccess($deposit, 0.0, $data['razorpay_payment_id']);

            return (new static())->returnSuccess('Payment Successful');
        }

        return (new static())->returnError('Something Went Wrong');
    }
}
