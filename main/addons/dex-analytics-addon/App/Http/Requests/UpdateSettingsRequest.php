<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'polling.interval_seconds' => ['nullable', 'integer', 'min:10'],
            'polling.refresh_interval_seconds' => ['nullable', 'integer', 'min:60'],
            'polling.max_retries' => ['nullable', 'integer', 'min:0'],
            'polling.backoff_seconds' => ['nullable', 'integer', 'min:0'],
            'retention.raw_days' => ['nullable', 'integer', 'min:1'],
            'retention.aggregate_days' => ['nullable', 'integer', 'min:0'],
            'leaderboards.min_trades' => ['nullable', 'integer', 'min:0'],
            'leaderboards.min_volume' => ['nullable', 'numeric', 'min:0'],
            'leaderboards.confidence_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
