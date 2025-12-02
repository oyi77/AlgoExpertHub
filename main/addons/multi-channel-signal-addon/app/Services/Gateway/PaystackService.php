<?php

namespace Addons\MultiChannelSignalAddon\App\Services\Gateway;

use App\Helpers\Helper\Helper;
use Addons\MultiChannelSignalAddon\App;
use Addons\MultiChannelSignalAddon\App;
use Illuminate\Http\Request;

class PaystackService
{
    public function success(Request $request)
    {
        if (isset($request['reference'])) {

            if (session('type') == 'deposit') {

                $message = 'Deposit Successfull';
                $payment = Deposit::where('trx', session('trx'))->first();
            } else {

                $message = 'Payment Successfull';
                $payment = Payment::where('trx', session('trx'))->first();
            }

            Helper::paymentSuccess($payment, $payment->charge, $request['reference']);

            return ['type' => 'success', 'message' => 'Payment Successfuly received'];
        }
    }
}
