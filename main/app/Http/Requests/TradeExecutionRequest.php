<?php

namespace App\Http\Requests;

use App\Rules\ValidTradingSymbol;
use Illuminate\Foundation\Http\FormRequest;

class TradeExecutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'connection_id' => ['required', 'integer', 'exists:execution_connections,id'],
            'symbol' => ['required', 'string', 'min:3', 'max:20', new ValidTradingSymbol],
            'direction' => ['required', 'string', 'in:buy,sell,long,short'],
            'lot_size' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'order_type' => ['required', 'string', 'in:market,limit'],
            'entry_price' => ['required_if:order_type,limit', 'nullable', 'numeric', 'min:0'],
            'sl_price' => ['nullable', 'numeric', 'min:0'],
            'tp_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'connection_id.required' => 'Please select an exchange connection.',
            'connection_id.exists' => 'The selected exchange connection is invalid.',
            'symbol.required' => 'Trading symbol is required.',
            'symbol.min' => 'Trading symbol must be at least 3 characters.',
            'direction.required' => 'Trade direction (buy/sell) is required.',
            'direction.in' => 'Trade direction must be buy, sell, long, or short.',
            'lot_size.required' => 'Lot size is required.',
            'lot_size.min' => 'Lot size must be at least 0.01.',
            'lot_size.max' => 'Lot size cannot exceed 100 lots.',
            'order_type.required' => 'Order type is required.',
            'order_type.in' => 'Order type must be market or limit.',
            'entry_price.required_if' => 'Entry price is required for limit orders.',
            'entry_price.min' => 'Entry price must be greater than zero.',
            'sl_price.min' => 'Stop loss price must be greater than zero.',
            'tp_price.min' => 'Take profit price must be greater than zero.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate SL/TP logic relative to entry price
            $this->validateStopLoss($validator);
            $this->validateTakeProfit($validator);
        });
    }

    /**
     * Validate stop loss makes sense relative to entry price
     */
    protected function validateStopLoss($validator)
    {
        $slPrice = $this->input('sl_price');
        $entryPrice = $this->input('entry_price');
        $direction = strtolower($this->input('direction', ''));

        if (!$slPrice || !$entryPrice) {
            return;
        }

        if (in_array($direction, ['buy', 'long'])) {
            // For buy orders, SL should be below entry
            if ($slPrice >= $entryPrice) {
                $validator->errors()->add('sl_price', 'Stop loss must be below entry price for buy orders.');
            }
        } elseif (in_array($direction, ['sell', 'short'])) {
            // For sell orders, SL should be above entry
            if ($slPrice <= $entryPrice) {
                $validator->errors()->add('sl_price', 'Stop loss must be above entry price for sell orders.');
            }
        }
    }

    /**
     * Validate take profit makes sense relative to entry price
     */
    protected function validateTakeProfit($validator)
    {
        $tpPrice = $this->input('tp_price');
        $entryPrice = $this->input('entry_price');
        $direction = strtolower($this->input('direction', ''));

        if (!$tpPrice || !$entryPrice) {
            return;
        }

        if (in_array($direction, ['buy', 'long'])) {
            // For buy orders, TP should be above entry
            if ($tpPrice <= $entryPrice) {
                $validator->errors()->add('tp_price', 'Take profit must be above entry price for buy orders.');
            }
        } elseif (in_array($direction, ['sell', 'short'])) {
            // For sell orders, TP should be below entry
            if ($tpPrice >= $entryPrice) {
                $validator->errors()->add('tp_price', 'Take profit must be below entry price for sell orders.');
            }
        }
    }
}
