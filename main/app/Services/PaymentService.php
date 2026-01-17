<?php

namespace App\Services;

use App\Helpers\Helper\Helper;
use Illuminate\Support\Str;
use App\Models\Gateway;
use App\Models\Deposit;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Wallet;

class PaymentService
{
    public function payNow($request)
    {
        $gateway = Gateway::where('status', 1)->find($request->id);

        if(!$gateway){
            return ['type' => 'error' , 'message' => 'Gateway Not Found'];
        }

        $trx = Str::upper(Str::random(16));

        $final_amount = $request->amount * $gateway->rate + $gateway->charge;

        if (isset($request->type) && $request->type == 'deposit') {

            $deposit = Deposit::create([
                'gateway_id' => $gateway->id,
                'user_id' => auth()->id(),
                'trx' => $trx,
                'amount' => $request->amount,
                'rate' => $gateway->rate,
                'charge' => $gateway->charge,
                'total' => $final_amount,
                'status' => 0,
                'type' => 1,
            ]);

            session()->put('trx', $trx);
            session()->put('type', 'deposit');

            return ['type' => 'deposit' , 'message' => route('user.gateway.details', $gateway->id)];
        }

        $plan = Plan::find($request->plan_id);

        if(!$plan){
            return ['type' => 'error' , 'message' => 'Plan Not Found'];
        }

        $plan_expired_at = $plan->plan_type === 'limited' ? now()->addDays($plan->duration) : now()->addYear(50);

        $payment = Payment::create([
            'plan_id' => $plan->id,
            'gateway_id' => $gateway->id,
            'user_id' => auth()->id(),
            'trx' => $trx,
            'amount' => $request->amount,
            'rate' => $gateway->rate,
            'charge' => $gateway->charge,
            'total' => $final_amount,
            'status' => 0,
            'type' => $gateway->type,
            'plan_expired_at' => $plan_expired_at

        ]);

        session()->put('trx', $trx);
        session()->put('type', 'payment');

        return ['type' => 'payment' , 'message' => route('user.gateway.details', $gateway->id)];
    }

    public function details($id)
    {
        $data['gateway'] = Gateway::where('status', 1)->where('id', $id)->first();

        if(!$data['gateway']){
            return ['type' => 'error', 'message' => 'No Gateway Found'];
        }

        $data['title'] = $data['gateway']->name . ' Payment Details';

        if (session('type') == 'deposit') {

            $data['deposit'] = Deposit::where('trx', session('trx'))->first();

        } else {

            $data['deposit'] = Payment::where('trx', session('trx'))->first();
        }

        if(!$data['deposit']){
            return ['type' => 'error', 'message' => 'Not Found'];
        }

        if ($data['gateway']->name == 'vougepay') {
            $vouguePayParams["marchant_id"] = $data['gateway']->parameter->vougepay_merchant_id;
            $vouguePayParams["redirect_url"] = route("user.payment.success", 'vougepay');
            $vouguePayParams["currency"] = $data['gateway']->parameter->gateway_currency;
            $vouguePayParams["merchant_ref"] = $data['deposit']->trx;
            $vouguePayParams["memo"] = "Payment";
            $vouguePayParams["store_id"] = $data['deposit']->user_id;
            $vouguePayParams["loadText"] = $data['deposit']->trx;
            $vouguePayParams["amount"] = $data['deposit']->total;
            $vouguePayParams = json_decode(json_encode($vouguePayParams));

            $data['vouguePayParams'] = $vouguePayParams;
        }

        if ($data['gateway']->type == 0) {
            return ['type'=> '','view' => Helper::theme().'user.gateway.offline', 'data' => $data];
        }

        return ['type'=> '', 'view' => Helper::theme().'user.gateway.online', 'data' => $data];
    }

    public function processRenewal(PlanSubscription $planSubscription): array
    {
        // Validate subscription is active
        if (!$planSubscription->is_current) {
            return [
                'success' => false,
                'message' => 'Subscription is not active'
            ];
        }

        // Load plan data
        $plan = Plan::find($planSubscription->plan_id);
        if (!$plan) {
            return [
                'success' => false,
                'message' => 'Plan not found'
            ];
        }

        // Get default gateway for renewal
        $gateway = Gateway::where('status', 1)->first();
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'No active gateway available'
            ];
        }

        // Calculate expiry date based on plan type
        $planExpiredAt = $plan->plan_type === 'limited'
            ? now()->addDays($plan->duration)
            : now()->addYear(50);

        // Create renewal payment record
        $payment = Payment::create([
            'plan_id' => $plan->id,
            'gateway_id' => $gateway->id,
            'user_id' => $planSubscription->user_id,
            'trx' => Str::upper(Str::random(16)),
            'amount' => $plan->price,
            'rate' => $gateway->rate,
            'charge' => $gateway->charge,
            'total' => ($plan->price * $gateway->rate) + $gateway->charge,
            'status' => 0, // Pending
            'type' => $gateway->type,
            'plan_expired_at' => $planExpiredAt
        ]);

        return [
            'success' => true,
            'message' => 'Renewal payment created successfully',
            'payment_id' => $payment->id,
            'trx' => $payment->trx
        ];
    }
}
