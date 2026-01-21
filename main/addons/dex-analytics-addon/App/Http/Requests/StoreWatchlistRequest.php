<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_address' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'assigned_user_id' => ['nullable', 'integer'],
        ];
    }
}
