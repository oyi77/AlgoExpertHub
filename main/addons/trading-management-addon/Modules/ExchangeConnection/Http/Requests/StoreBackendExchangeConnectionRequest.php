<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBackendExchangeConnectionRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'connection_type' => 'required|in:CRYPTO_EXCHANGE,FX_BROKER',
            'provider' => 'required|string',
            'credentials' => 'required|array',
            'credentials.api_key' => 'required_without:credentials.account_id|string',
            'credentials.api_secret' => 'required_without:credentials.account_id|string',
            'credentials.api_passphrase' => 'nullable|string',
            'credentials.account_id' => 'required_without:credentials.api_key|string',
            'data_fetching_enabled' => 'nullable|boolean',
            'trade_execution_enabled' => 'nullable|boolean',
            'preset_id' => 'nullable|exists:trading_presets,id',
            'data_settings' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Connection Name is required. Please enter a name for this connection.'),
            'name.max' => __('Connection Name cannot exceed 255 characters. Please use a shorter name.'),
            'connection_type.required' => __('Connection Type is required. Please select Crypto Exchange or Forex Broker.'),
            'connection_type.in' => __('Invalid Connection Type selected. Please choose Crypto Exchange or Forex Broker.'),
            'provider.required' => __('Provider/Exchange is required. Please select the exchange or broker provider you want to connect to.'),
            'credentials.required' => __('API Credentials are required. Please provide your exchange API credentials.'),
            'credentials.array' => __('Credentials must be provided as an array. Please check your form submission.'),
            'credentials.api_key.required_without' => __('API Key is required. You can find this in your exchange account settings under API Management. Create a new API key if you don\'t have one.'),
            'credentials.api_secret.required_without' => __('API Secret is required. This is shown only once when you create the API key. If you lost it, you need to create a new API key.'),
            'credentials.account_id.required_without' => __('MetaApi Account ID is required. Add your MT account to MetaApi first, then copy the Account ID from your MetaApi dashboard.'),
            'preset_id.exists' => __('Selected Trading Preset does not exist. Please select a valid preset or leave it as None.'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('Connection Name'),
            'connection_type' => __('Connection Type'),
            'provider' => __('Provider/Exchange'),
            'credentials' => __('API Credentials'),
            'credentials.api_key' => __('API Key'),
            'credentials.api_secret' => __('API Secret'),
            'credentials.api_passphrase' => __('API Passphrase'),
            'credentials.account_id' => __('MetaApi Account ID'),
            'preset_id' => __('Trading Preset'),
        ];
    }
}

