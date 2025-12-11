<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Transactions
 *
 * Endpoints for viewing transaction history.
 */
class TransactionApiController extends Controller
{
    /**
     * Get Transactions
     *
     * Retrieve user's transaction history.
     *
     * @queryParam type string Filter by type: + (credit) or - (debit). Example: +
     * @queryParam search string Search by transaction ID or details. Example: deposit
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id());

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('trx', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get Transaction Details
     *
     * Retrieve details of a specific transaction.
     *
     * @urlParam trx string required Transaction ID. Example: ABC123XYZ
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function show($trx)
    {
        $transaction = Transaction::where('user_id', Auth::id())
            ->where('trx', $trx)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Get Transaction Summary
     *
     * Get summary statistics of transactions.
     *
     * @queryParam period string Period: today, week, month, year. Example: month
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "total_credit": 1000,
     *     "total_debit": 500,
     *     "net": 500,
     *     "count": 25
     *   }
     * }
     */
    public function summary(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id());

        // Apply period filter
        $period = $request->get('period', 'month');
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $totalCredit = (clone $query)->where('type', '+')->sum('amount');
        $totalDebit = (clone $query)->where('type', '-')->sum('amount');
        $count = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_credit' => $totalCredit,
                'total_debit' => $totalDebit,
                'net' => $totalCredit - $totalDebit,
                'count' => $count,
                'period' => $period
            ]
        ]);
    }
}
