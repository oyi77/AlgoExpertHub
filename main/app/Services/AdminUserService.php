<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Configuration;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminUserService
{
    public function update($request)
    {
        $user = User::find($request->user);
        
        // ✅ Bug #8 Fix: Add null check
        if (!$user) {
            return ['type' => 'error', 'message' => 'User not found'];
        }

        $data = [
            'country' => $request->country,
            'city' => $request->city,
            'zip' => $request->zip,
            'state' => $request->state,
        ];

        $user->phone = $request->phone;
        $user->address = $data;
        $user->status = $request->status == 'on' ? 1 : 0;
        $user->is_email_verified = $request->email_status == 'on' ? 1 : 0;
        $user->is_sms_verified = $request->sms_status == 'on' ? 1 : 0;
        $user->is_kyc_verified = $request->kyc_status == 'on' ? 1 : 0;

        $user->save();


        return ['type' => 'success', 'message' => 'Successfully Updated User Profile'];
    }

    public function updateBalance($request)
    {
        // ✅ Bug #5 Fix: Use transaction wrapper and atomic updates
        // ✅ Potential Issue #3 Fix: Wrap in transaction
        return DB::transaction(function () use ($request) {
            $user = User::findOrFail($request->user_id);

            $general = Configuration::first();

            if ($request->type == 'add') {
                // ✅ Bug #5 Fix: Use atomic increment
                $user->increment('balance', $request->balance);
            } else {
                if ($user->balance < $request->balance) {
                    return ['type' => 'error', 'message' => 'Insufficient balance'];
                }
                // ✅ Bug #5 Fix: Use atomic decrement
                $user->decrement('balance', $request->balance);
            }

            $trx = strtoupper(Str::random());

            Transaction::create([
                'trx' => $trx,
                'user_id' => $user->id,
                'amount' => $request->balance,
                'charge' => 0,
                'details' => $request->type === 'add' ? 'Balance Added By Admin' : 'Balance Subtruct By Admin',
                'type' => $request->type === 'add' ? '+' : '-'
            ]);

            return ['type' => 'success', 'message' => 'Successfully ' . $request->type . ' balance'];
        });
    }
}
