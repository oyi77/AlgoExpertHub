<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Http\Requests\StoreBackendExchangeConnectionRequest;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\CcxtExchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HandlesCrudOperations
{
    public function index()
    {
        $title = 'Exchange Connections';
        $connections = ExchangeConnection::with(['admin', 'user', 'preset'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.exchange-connections.index', compact('title', 'connections'));
    }

    public function create()
    {
        $title = 'Create Exchange Connection';
        $presets = TradingPreset::where('is_default_template', 1)->get();
        
        return view('trading-management::backend.exchange-connections.create', compact('title', 'presets'));
    }

    /**
     * Get list of CCXT-supported crypto exchanges
     */
    public function getCcxtExchanges()
    {
        try {
            $service = new CcxtExchangeService();
            $exchanges = $service->getCryptoExchanges();
            
            return response()->json([
                'success' => true,
                'exchanges' => $exchanges,
                'count' => count($exchanges)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load exchanges: ' . $e->getMessage(),
                'exchanges' => []
            ], 500);
        }
    }

    public function store(StoreBackendExchangeConnectionRequest $request)
    {
        $validated = $request->validated();

        // Validate credentials based on provider
        if ($validated['provider'] === 'metaapi') {
            if (empty($validated['credentials']['account_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['credentials.account_id' => __('MetaApi Account ID is required. Add your MT account to MetaApi first, then copy the Account ID from your MetaApi dashboard.')]);
            }
            // Auto-fill api_token from config if not provided
            if (empty($validated['credentials']['api_token'])) {
                $validated['credentials']['api_token'] = config('trading-management.metaapi.api_token');
            }
        }

        // Map connection_type to type (legacy support)
        $type = $validated['connection_type'] === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
        
        // Map provider to exchange_name (legacy support)
        $exchangeName = $validated['provider'] ?? null;
        
        // Prepare data settings - only include if it has actual content
        $dataSettings = null;
        if (!empty($validated['data_settings']) && is_array($validated['data_settings'])) {
            // Check if array has any non-empty values
            $hasContent = false;
            foreach ($validated['data_settings'] as $value) {
                if (is_array($value) && !empty($value)) {
                    $hasContent = true;
                    break;
                } elseif (!is_array($value) && $value !== '' && $value !== null) {
                    $hasContent = true;
                    break;
                }
            }
            if ($hasContent) {
                $dataSettings = $validated['data_settings'];
            }
        }
        
        // Build connection data
        $connectionData = [
            'name' => $validated['name'],
            'connection_type' => $validated['connection_type'],
            'type' => $type,
            'provider' => $validated['provider'],
            'exchange_name' => $exchangeName,
            'credentials' => $validated['credentials'], // Encrypted by HasEncryptedCredentials trait
            'data_fetching_enabled' => (bool) ($validated['data_fetching_enabled'] ?? false),
            'trade_execution_enabled' => (bool) ($validated['trade_execution_enabled'] ?? false),
            'admin_id' => auth()->guard('admin')->id(),
            'is_admin_owned' => true,
            'status' => 'inactive',
            'is_active' => false,
        ];
        
        // Only add data_settings if it has content (model cast will handle JSON encoding)
        if ($dataSettings !== null) {
            $connectionData['data_settings'] = $dataSettings;
        }
        
        // Add preset_id if provided
        if (!empty($validated['preset_id'])) {
            $connectionData['preset_id'] = $validated['preset_id'];
        }
        
        $connection = ExchangeConnection::create($connectionData);

        return redirect()->route('admin.trading-management.config.exchange-connections.show', $connection)
            ->with('success', 'Exchange connection created. Test it below.');
    }

    public function show(ExchangeConnection $exchangeConnection)
    {
        $title = 'Exchange Connection - ' . $exchangeConnection->name;
        $connection = $exchangeConnection->load('preset');
        
        // Handle decrypt errors gracefully - show warning if credentials are corrupted
        $credentialsValid = true;
        try {
            $creds = $exchangeConnection->credentials;
            $rawCreds = $exchangeConnection->getAttributes()['credentials'] ?? null;
            if (empty($creds) && !empty($rawCreds)) {
                $credentialsValid = false;
            }
        } catch (\Exception $e) {
            $credentialsValid = false;
        }
        
        return view('trading-management::backend.exchange-connections.show', compact('title', 'connection', 'credentialsValid'));
    }

    /**
     * Show edit form
     */
    public function edit(ExchangeConnection $exchangeConnection)
    {
        $title = 'Edit Exchange Connection';
        
        // Handle decrypt errors gracefully
        $credentialsValid = true;
        $credentials = [];
        try {
            $credentials = $exchangeConnection->credentials;
            $rawCreds = $exchangeConnection->getAttributes()['credentials'] ?? null;
            if (empty($credentials) && !empty($rawCreds)) {
                $credentialsValid = false;
            }
        } catch (\Exception $e) {
            $credentialsValid = false;
        }
        
        $presets = TradingPreset::where('is_default_template', 1)->get();
        
        return view('trading-management::backend.exchange-connections.edit', compact('title', 'exchangeConnection', 'presets', 'credentialsValid', 'credentials'));
    }

    /**
     * Update connection
     */
    public function update(Request $request, ExchangeConnection $exchangeConnection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'connection_type' => 'required|in:CRYPTO_EXCHANGE,FX_BROKER',
            'provider' => 'required|string',
            'credentials' => 'required|array',
            'data_fetching_enabled' => 'nullable|boolean',
            'trade_execution_enabled' => 'nullable|boolean',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_settings' => 'nullable|array',
        ]);

        // Validate credentials based on provider
        if ($validated['provider'] === 'metaapi') {
            if (empty($validated['credentials']['account_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['credentials.account_id' => 'MetaApi Account ID is required']);
            }
            // Auto-fill api_token from config if not provided
            if (empty($validated['credentials']['api_token'])) {
                $validated['credentials']['api_token'] = config('trading-management.metaapi.api_token');
            }
        }

        // Map connection_type to type (legacy support)
        $type = $validated['connection_type'] === 'CRYPTO_EXCHANGE' ? 'crypto' : 'fx';
        
        // Map provider to exchange_name (legacy support)
        $exchangeName = $validated['provider'] ?? null;
        
        // Prepare data settings - only include if it has actual content
        $dataSettings = null;
        if (!empty($validated['data_settings']) && is_array($validated['data_settings'])) {
            $hasContent = false;
            foreach ($validated['data_settings'] as $value) {
                if (is_array($value) && !empty($value)) {
                    $hasContent = true;
                    break;
                } elseif (!is_array($value) && $value !== '' && $value !== null) {
                    $hasContent = true;
                    break;
                }
            }
            if ($hasContent) {
                $dataSettings = $validated['data_settings'];
            }
        }
        
        // Build update data
        $updateData = [
            'name' => $validated['name'],
            'connection_type' => $validated['connection_type'],
            'type' => $type,
            'provider' => $validated['provider'],
            'exchange_name' => $exchangeName,
            'credentials' => $validated['credentials'], // Will be encrypted by HasEncryptedCredentials trait
            'data_fetching_enabled' => (bool) ($validated['data_fetching_enabled'] ?? false),
            'trade_execution_enabled' => (bool) ($validated['trade_execution_enabled'] ?? false),
        ];
        
        // Only add data_settings if it has content
        if ($dataSettings !== null) {
            $updateData['data_settings'] = $dataSettings;
        }
        
        // Add preset_id if provided
        if (!empty($validated['preset_id'])) {
            $updateData['preset_id'] = $validated['preset_id'];
        }
        
        $exchangeConnection->update($updateData);

        return redirect()->route('admin.trading-management.config.exchange-connections.show', $exchangeConnection)
            ->with('success', 'Exchange connection updated successfully.');
    }

    /**
     * Delete connection
     */
    public function destroy(ExchangeConnection $exchangeConnection)
    {
        try {
            $connectionName = $exchangeConnection->name;
            $exchangeConnection->delete();

            return redirect()->route('admin.trading-management.config.exchange-connections.index')
                ->with('success', "Exchange connection '{$connectionName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to delete exchange connection', [
                'connection_id' => $exchangeConnection->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.trading-management.config.exchange-connections.index')
                ->with('error', 'Failed to delete connection. Please try again.');
        }
    }

    /**
     * Transfer ownership of connection to a user
     */
    public function transferOwnership(Request $request, ExchangeConnection $exchangeConnection)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = \App\Models\User::findOrFail($validated['user_id']);
            
            // Update ownership
            $exchangeConnection->update([
                'user_id' => $user->id,
                'admin_id' => null,
                'is_admin_owned' => false,
            ]);

            Log::info('Exchange connection ownership transferred', [
                'connection_id' => $exchangeConnection->id,
                'connection_name' => $exchangeConnection->name,
                'new_user_id' => $user->id,
                'new_user_email' => $user->email,
            ]);

            return redirect()->route('admin.trading-management.config.exchange-connections.index')
                ->with('success', "Connection '{$exchangeConnection->name}' ownership transferred to {$user->username} ({$user->email}) successfully.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to transfer exchange connection ownership', [
                'connection_id' => $exchangeConnection->id,
                'user_id' => $validated['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to transfer ownership. Please try again.')
                ->withInput();
        }
    }
}

