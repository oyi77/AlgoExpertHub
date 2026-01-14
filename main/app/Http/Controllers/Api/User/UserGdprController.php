<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PlanSubscription;
use App\Models\Payment;
use App\Models\TradingBot;
use App\Models\Trade;
use App\Models\MoneyTransfer;
use App\Models\Withdraw;
use App\Models\UserLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserGdprController extends Controller
{
    /**
     * Export all user data (GDPR right to data portability).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'address' => $user->address,
                'status' => $user->status,
                'balance' => (string) $user->balance,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
            ],
            'subscriptions' => $user->subscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'plan_id' => $sub->plan_id,
                    'plan_name' => $sub->plan->name,
                    'status' => $sub->is_current ? 'active' : 'inactive',
                    'created_at' => $sub->created_at?->toIso8601String(),
                    'expired_at' => $sub->plan_expired_at?->toIso8601String(),
                ];
            })->toArray(),
            'payments' => Payment::where('user_id', $user->id)->get()->map(function ($payment) {
                return [
                    'trx' => $payment->trx,
                    'amount' => (string) $payment->amount,
                    'charge' => (string) $payment->charge,
                    'total' => (string) $payment->total,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'deposits' => Deposit::where('user_id', $user->id)->get()->map(function ($deposit) {
                return [
                    'trx' => $deposit->trx,
                    'amount' => (string) $deposit->amount,
                    'charge' => (string) $deposit->charge,
                    'total' => (string) $deposit->total,
                    'status' => $deposit->status,
                    'created_at' => $deposit->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'withdrawals' => Withdraw::where('user_id', $user->id)->get()->map(function ($withdraw) {
                return [
                    'trx' => $withdraw->trx,
                    'amount' => (string) $withdraw->withdraw_amount,
                    'charge' => (string) $withdraw->withdraw_charge,
                    'total' => (string) $withdraw->total,
                    'status' => $withdraw->status,
                    'created_at' => $withdraw->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'money_transfers' => MoneyTransfer::where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->get()
                ->map(function ($transfer) {
                    return [
                        'trx' => $transfer->trx,
                        'amount' => (string) $transfer->amount,
                        'charge' => (string) $transfer->charge,
                        'sender_id' => $transfer->sender_id,
                        'receiver_id' => $transfer->receiver_id,
                        'created_at' => $transfer->created_at?->toIso8601String(),
                    ];
                })->toArray(),
            'trades' => Trade::where('user_id', $user->id)->get()->map(function ($trade) {
                return [
                    'id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'type' => $trade->type,
                    'entry_price' => (string) $trade->entry_price,
                    'exit_price' => (string) $trade->exit_price,
                    'quantity' => (string) $trade->quantity,
                    'profit' => (string) $trade->profit,
                    'status' => $trade->status,
                    'created_at' => $trade->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'activity_logs' => UserLog::where('user_id', $user->id)->limit(500)->get()->map(function ($log) {
                return [
                    'action' => $log->action,
                    'details' => $log->details,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->json([
            'type' => 'success',
            'message' => 'Data exported successfully',
            'data' => $data,
        ])
        ->header('Content-Disposition', 'attachment; filename="user-data-' . $user->id . '.json"');
    }

    /**
     * Request account deletion (GDPR right to be forgotten).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'confirmation' => 'required|in:DELETE MY ACCOUNT',
        ]);

        $user = $request->user();

        $user->deletion_requested_at = now();
        $user->deletion_reason = $request->input('reason');
        $user->save();

        return response()->json([
            'type' => 'success',
            'message' => 'Deletion request received. Your account will be deleted within 30 days.',
        ]);
    }
}
