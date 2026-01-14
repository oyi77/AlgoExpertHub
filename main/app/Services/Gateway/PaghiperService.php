<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaghiperService extends BaseAdapter
{
    /**
     * Process payment with Paghiper.
     */
    public static function process($request, $gateway, float $amount, $deposit): array
    {
        \PagHipperSDK\Auth::init(
            $gateway->parameter->paghiper_key,
            $gateway->parameter->token
        );

        $pagHiper = new \PagHipperSDK\PagHiper();
        $items = [];
        $items[] = (new \PagHipperSDK\Entities\Item())
            ->setItemId((string)$deposit->id)
            ->setDescription('Plan Purchase')
            ->setQuantity(1)
            ->setPriceCents((int)($amount * 100));

        $user = auth()->user();
        $payer = (new \PagHipperSDK\Entities\Payer())
            ->setPayerEmail($user->email)
            ->setPayerName($user->username)
            ->setPayerCpfCnpj($request->cpf);

        $transaction = (new \PagHipperSDK\Entities\Transaction())
            ->setOrderId($deposit->trx)
            ->setNotificationUrl(route('user.payment.success', $gateway->name))
            ->setShippingMethods('PAC')
            ->setFixedDescription(true)
            ->setDaysDueDate('3')
            ->setPayer($payer)
            ->setItems($items);

        $transaction = $pagHiper->createTransaction($transaction);

        return (new static())->successResponse('Redirect to Paghiper', ['redirect_url' => $transaction->getBankSlip()->getUrlSlip()]);
    }

    /**
     * Handle success callback from Paghiper.
     */
    public function success(Request $request): array
    {
        $transaction = \PagHipperSDK\Response\GetTransactionPix::populate($request->all());
        $trx = session('trx');
        $type = session('type');

        if ($type === 'deposit') {
            $deposit = Deposit::where('trx', $trx)->first();
        } else {
            $deposit = Payment::where('trx', $trx)->first();
        }

        if (!$deposit) {
            return $this->returnError('Transaction not found');
        }

        if (isset($request->transaction_id)) {
            $this->handlePaymentSuccess($deposit, 0.0, $transaction->getOrderId());

            return $this->returnSuccess('Plan Subscribed Successfully');
        }

        return $this->returnError('Payment verification failed');
    }
}
