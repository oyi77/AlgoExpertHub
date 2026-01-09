<?php

namespace Addons\TradingManagement\Modules\TradingBot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;

class StoreTradingBotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = auth()->id();
        $isAdmin = auth()->guard('admin')->check();
        
        // Build exchange connection validation
        $connectionRule = [
            'required',
            'exists:execution_connections,id',
        ];
        
        // Add ownership check
        if (!$isAdmin) {
            $connectionRule[] = function ($attribute, $value, $fail) use ($userId) {
                $connection = ExchangeConnection::find($value);
                if (!$connection) {
                    return; // Let exists rule handle this
                }
                
                // User must own the connection (not admin-owned)
                if ($connection->is_admin_owned || $connection->user_id !== $userId) {
                    $fail('The selected exchange connection does not belong to you. Please select your own connection or create a new one.');
                }
                
                // Connection must be active
                if (!$connection->is_active) {
                    $fail('The selected exchange connection is not active. Please activate the connection first or select an active connection.');
                }
                
                // Connection status must be 'active'
                if ($connection->status !== 'active') {
                    $fail('The selected exchange connection is not ready. Please ensure the connection is tested and active. Current status: ' . $connection->status);
                }
                
                // For crypto exchanges, validate credentials are present
                if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
                    $credentials = $connection->credentials;
                    $exchangeName = strtolower($connection->exchange_name ?? $connection->provider ?? '');
                    
                    // Check for required credentials
                    if (empty($credentials['api_key']) || empty($credentials['api_secret'])) {
                        $fail('The selected exchange connection is missing required API credentials. Please update the connection with valid API key and secret.');
                    }
                    
                    // Check for passphrase requirement
                    $requiresPassphrase = in_array($exchangeName, ['okx', 'kucoin', 'coinbasepro', 'coinbase']);
                    if ($requiresPassphrase && empty($credentials['api_passphrase'])) {
                        $fail('The selected exchange connection requires an API passphrase. Please update the connection with a valid passphrase for ' . strtoupper($exchangeName) . '.');
                    }
                }
            };
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('trading_bots')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                })->ignore($this->route('id')),
            ],
            'description' => 'nullable|string',
            'exchange_connection_id' => $connectionRule,
            'trading_preset_id' => [
                'required',
                'exists:trading_presets,id',
            ],
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'expert_advisor_id' => 'nullable|exists:expert_advisors,id',
            'trading_mode' => 'required|in:SIGNAL_BASED,MARKET_STREAM_BASED',
            'data_connection_id' => 'nullable|exists:execution_connections,id',
            'streaming_symbols' => 'nullable|array',
            'streaming_symbols.*' => 'nullable|string',
            'streaming_symbols_manual' => 'nullable|string',
            'streaming_timeframes' => 'nullable|array',
            'streaming_timeframes.*' => 'nullable|string',
            'market_analysis_interval' => 'nullable|integer|min:10',
            'position_monitoring_interval' => 'nullable|integer|min:1',
            'is_paper_trading' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bot name is required.',
            'name.unique' => 'You already have a bot with this name. Please choose a different name.',
            'exchange_connection_id.required' => 'Exchange connection is required. Please select a connection or create a new one.',
            'exchange_connection_id.exists' => 'The selected exchange connection does not exist. Please select a valid connection.',
            'trading_preset_id.required' => 'Trading preset is required. Please select a preset.',
            'trading_preset_id.exists' => 'The selected trading preset does not exist. Please select a valid preset.',
            'trading_mode.required' => 'Trading mode is required. Please select Signal Based or Market Stream Based.',
            'trading_mode.in' => 'Invalid trading mode selected. Please choose Signal Based or Market Stream Based.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'bot name',
            'exchange_connection_id' => 'exchange connection',
            'trading_preset_id' => 'trading preset',
            'trading_mode' => 'trading mode',
        ];
    }
}

