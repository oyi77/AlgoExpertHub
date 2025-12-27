<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Helper\Helper;
use App\Models\Admin;
use App\Models\Configuration;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Template;
use App\Models\Transaction;
use App\Notifications\PlanSubscriptionNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserPlanService extends BaseService
{
    /**
     * Subscribe a user to a plan.
     * 
     * @param Request $request
     * @return array
     */
    public function subscribe(Request $request): array
    {
        return $this->executeInTransaction(function () use ($request) {
            $general = Configuration::first();
            $plan = Plan::find($request->payment);

            if (!$plan) {
                return $this->errorResponse('Plan Not Found', [], 404);
            }

            $user = auth()->user();
            $data = [
                'user_id' => $user->id,
                'transaction_id' => Str::upper(Str::random(16)),
                'amount' => (float)($plan->price ?? 0),
                'gateway_id' => null,
                'plan_id' => $plan->id,
                'charge' => 0.0,
                'rate' => 1.0,
            ];

            $data['final_amount'] = ($data['amount'] * $data['rate']) + $data['charge'];

            if ($plan->price_type === 'free') {
                return $this->handleFreeSubscription($plan, $data);
            }

            if ($request->payment_type === 'balance') {
                return $this->handleBalanceSubscription($user, $plan, $data);
            }

            $isAlreadySubscribed = $user->subscriptions()
                ->where('plan_id', $plan->id)
                ->where('is_current', 1)
                ->first();

            if ($isAlreadySubscribed) {
                return $this->errorResponse('Already Subscribed to this plan');
            }

            return [
                'type' => 'redirect',
                'message' => route('user.gateways', $plan->id)
            ];
        });
    }

    /**
     * Handle free plan subscription.
     */
    protected function handleFreeSubscription(Plan $plan, array $data): array
    {
        $data['plan_expired_at'] = $plan->plan_type === 'limited' ? now()->addDays($plan->duration) : now()->addYear(50);
        $data['payment_status'] = 1;
        $data['payment_type'] = 1;

        $subscription = $this->createSubscription($data);
        
        $admin = Admin::where('type', 'super')->first();
        if ($admin) {
            $admin->notify(new PlanSubscriptionNotification($subscription));
        }

        return $this->successResponse('Subscription on a free plan');
    }

    /**
     * Handle subscription using user balance.
     */
    protected function handleBalanceSubscription($user, Plan $plan, array $data): array
    {
        if ($user->balance < $plan->price) {
            return $this->errorResponse('Insufficient Balance');
        }

        $data['plan_expired_at'] = $plan->plan_type === 'limited' ? now()->addDays($plan->duration) : now()->addYear(50);
        $data['payment_status'] = 1;
        $data['payment_type'] = 1;

        $subscription = $this->createSubscription($data);
        $payment = $this->makePayment($data);

        $user->balance -= $data['final_amount'];
        $user->save();

        $this->makeTransaction($data);

        if ($user->refferedBy) {
            Helper::referMoney($user->id, $user->refferedBy, 'invest', (float)$payment->amount);
        }

        $admin = Admin::where('type', 'super')->first();
        if ($admin) {
            $admin->notify(new PlanSubscriptionNotification($subscription));
        }

        $this->sendNotificationEmail($user, $payment);

        return $this->successResponse('Subscription successful');
    }

    /**
     * Create a new subscription record.
     */
    protected function createSubscription(array $data): PlanSubscription
    {
        auth()->user()->subscriptions()->where('is_current', 1)->update(['is_current' => 0]);

        return PlanSubscription::create([
            'plan_id' => $data['plan_id'],
            'user_id' => $data['user_id'],
            'is_current' => 1,
            'plan_expired_at' => $data['plan_expired_at']
        ]);
    }

    /**
     * Log a transaction.
     */
    protected function makeTransaction(array $data): void
    {
        Transaction::create([
            'trx' => $data['transaction_id'],
            'user_id' => $data['user_id'],
            'amount' => $data['final_amount'],
            'charge' => $data['charge'],
            'details' => 'Payment For Subscription',
            'type' => '-'
        ]);
    }

    /**
     * Create a payment record.
     */
    protected function makePayment(array $data): Payment
    {
        return Payment::create([
            'plan_id' => $data['plan_id'],
            'gateway_id' => $data['gateway_id'] ?? 0,
            'user_id' => $data['user_id'],
            'trx' => $data['transaction_id'],
            'amount' => $data['amount'],
            'rate' => $data['rate'],
            'charge' => $data['charge'],
            'total' => $data['final_amount'],
            'status' => $data['payment_status'],
            'plan_expired_at' => $data['plan_expired_at'],
        ]);
    }

    /**
     * Send subscription confirmation email.
     */
    protected function sendNotificationEmail($user, $payment): void
    {
        $template = Template::where('name', 'plan_subscription')->where('status', 1)->first();

        if ($template) {
            Helper::fireMail([
                'app_name' => Helper::config()->appname,
                'email' => $user->email,
                'username' => $user->username,
                'plan' => $payment->plan->name ?? 'deposit',
                'trx' => $payment->trx,
                'amount' => $payment->total,
            ], $template);
        }
    }
}
