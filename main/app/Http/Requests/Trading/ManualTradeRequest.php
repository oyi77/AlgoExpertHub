<?php

namespace App\Http\Requests\Trading;

use Illuminate\Foundation\Http\FormRequest;

class ManualTradeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'connection_id' => 'required|exists:execution_connections,id',
            'symbol' => 'required|string',
            'direction' => 'required|in:BUY,SELL,LONG,SHORT,buy,sell,long,short',
            'lot_size' => 'required|numeric|min:0.01',
            'order_type' => 'required|in:market,limit',
            'entry_price' => 'nullable|numeric|required_if:order_type,limit',
            'sl_price' => 'nullable|numeric',
            'tp_price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'entry_price.required_if' => 'Entry price is required for limit orders',
        ];
    }
}
