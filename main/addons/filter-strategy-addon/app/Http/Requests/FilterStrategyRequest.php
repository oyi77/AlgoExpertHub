<?php

namespace Addons\FilterStrategyAddon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterStrategyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:PRIVATE,PUBLIC_MARKETPLACE',
            'clonable' => 'boolean',
            'enabled' => 'boolean',
            'config' => 'required|json',
        ];
    }

    public function prepareForValidation()
    {
        // If config is array, convert to JSON
        if ($this->has('config') && is_array($this->config)) {
            $this->merge([
                'config' => json_encode($this->config),
            ]);
        }
    }
}

