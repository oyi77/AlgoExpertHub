<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Money Transfer
 *
 * Endpoints for transferring money between users.
 */
class TransferApiController extends Controller
{
    /**
     * Transfer Money
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $sender = Auth::user();
        $recipient = User::where('email', $request->recipient_email)->first();

        if ($recipient->id === $sender->id) {
            return response()->json(['success' => false, 'message' => 'Cannot transfer to yourself'], 400);
        }

        if ($sender->balance < $request->amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance'], 400);
        }

        // Deduct from sender
        $sender->balance -= $request->amount;
        $sender->save();

        Transaction::create([
            'user_id' => $sender->id,
            'amount' => $request->amount,
            'type' => '-',
            'details' => "Transfer to {$recipient->email}",
            'trx' => \Str::upper(\Str::random(12)),
        ]);

        // Add to recipient
        $recipient->balance += $request->amount;
        $recipient->save();

        Transaction::create([
            'user_id' => $recipient->id,
            'amount' => $request->amount,
            'type' => '+',
            'details' => "Transfer from {$sender->email}",
            'trx' => \Str::upper(\Str::random(12)),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transfer successful',
            'data' => [
                'amount' => $request->amount,
                'recipient' => $recipient->email,
                'new_balance' => $sender->balance
            ]
        ]);
    }

    /**
     * Transfer History
     */
    public function history()
    {
        $transfers = Transaction::where('user_id', Auth::id())
            ->where(function($q) {
                $q->where('details', 'like', '%Transfer to%')
                  ->orWhere('details', 'like', '%Transfer from%');
            })
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $transfers]);
    }
}
