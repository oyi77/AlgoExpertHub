<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\Request;
use Victorybiz\LaravelCryptoPaymentGateway\LaravelCryptoPaymentGateway;

class Gourl extends BaseAdapter
{
    /**
     * Process payment with Gourl.
     */
    public static function process($request, $gateway, float $amount, $deposit): array
    {
        $paymentUrl = LaravelCryptoPaymentGateway::startPaymentSession([
            'amountUSD' => $deposit->amount,
            'orderID' => $deposit->trx,
            'userID' => auth()->id(),
            'redirect' => url()->full(),
        ]);

        return (new static())->successResponse('Redirect to Crypto Box', ['redirect_url' => $paymentUrl]);
    }

    /**
     * Handle callback from Gourl.
     */
    public function callback(Request $request)
    {
        return LaravelCryptoPaymentGateway::callback();
    }

    /**
     * Handle success callback from Gourl.
     */
    public static function success($cryptoPaymentModel, array $paymentDetails, string $boxStatus): bool
    {
        if ($cryptoPaymentModel) {
            $trx = $cryptoPaymentModel->paymentID;
            $type = session('type');

            if ($type === 'deposit') {
                $userOrder = Deposit::where('trx', $trx)->first();
            } else {
                $userOrder = Payment::where('trx', $trx)->first();
            }

            if ($userOrder && $boxStatus === "cryptobox_updated") {
                $userOrder->txconfirmed = $paymentDetails["confirmed"];
                $userOrder->save();
            }

            if (!$cryptoPaymentModel->processed && $paymentDetails["confirmed"]) {
                (new static())->handlePaymentSuccess($userOrder, (float)$paymentDetails["amount"], (string)$trx);
            }
        }
        
        return true;
    }
}
