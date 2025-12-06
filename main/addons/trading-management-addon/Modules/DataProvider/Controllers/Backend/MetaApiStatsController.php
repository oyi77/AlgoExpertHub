<?php

namespace Addons\TradingManagement\Modules\DataProvider\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\DataProvider\Services\MetaApiProvisioningService;
use Illuminate\Http\Request;

/**
 * MetaApi Statistics Controller
 * 
 * Displays MetaApi account statistics (balance, spending power, accounts) in admin panel
 */
class MetaApiStatsController extends Controller
{
    /**
     * Show MetaApi statistics dashboard
     */
    public function index(Request $request)
    {
        $title = 'MetaApi Statistics';
        
        $provisioningService = new MetaApiProvisioningService();
        
        // Get billing info
        $billingInfo = $provisioningService->getBillingInfo();
        
        // Get account stats
        $accountStats = $provisioningService->getAccountStats();
        
        // Get accounts from our database
        try {
            // Try to query by provider column first
            $localConnections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('provider', 'metaapi')
                ->with('admin', 'user')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            // If provider column doesn't exist, filter by credentials
            try {
                $allConnections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::with('admin', 'user')
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $localConnections = $allConnections->filter(function($conn) {
                    // Filter by checking if credentials contain MetaApi-specific fields
                    $creds = $conn->credentials ?? [];
                    return isset($creds['account_id']) || isset($creds['api_token']) || 
                           (isset($creds['provider']) && $creds['provider'] === 'metaapi');
                });
            } catch (\Exception $e2) {
                \Log::warning('Failed to load MetaApi connections', [
                    'error' => $e->getMessage(), 
                    'fallback_error' => $e2->getMessage()
                ]);
                $localConnections = collect([]);
            }
        }
        
        // If AJAX request, return partial view
        if ($request->ajax() || $request->wantsJson()) {
            return view('trading-management::backend.metaapi.stats-content', compact(
                'billingInfo',
                'accountStats',
                'localConnections'
            ));
        }
        
        return view('trading-management::backend.metaapi.stats', compact(
            'title',
            'billingInfo',
            'accountStats',
            'localConnections'
        ));
    }

    /**
     * Refresh statistics (AJAX)
     */
    public function refresh(Request $request)
    {
        $provisioningService = new MetaApiProvisioningService();
        
        $billingInfo = $provisioningService->getBillingInfo();
        $accountStats = $provisioningService->getAccountStats();
        
        return response()->json([
            'success' => true,
            'billing' => $billingInfo,
            'accounts' => $accountStats,
        ]);
    }

    /**
     * Deposit to MetaApi account (AJAX)
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'terms_agreement' => 'required|accepted',
            'refund_agreement' => 'required|accepted',
        ]);

        $provisioningService = new MetaApiProvisioningService();
        
        $result = $provisioningService->deposit(
            (float) $request->amount,
            (bool) $request->terms_agreement,
            (bool) $request->refund_agreement
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'client_secret' => $result['client_secret'] ?? null,
                'data' => $result['data'] ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Deposit failed',
        ], 400);
    }
}
