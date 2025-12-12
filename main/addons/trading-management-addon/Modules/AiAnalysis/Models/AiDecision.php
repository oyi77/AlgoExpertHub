<?php

namespace Addons\TradingManagement\Modules\AiAnalysis\Models;

use App\Models\Signal;
use Illuminate\Database\Eloquent\Model;

class AiDecision extends Model
{
    protected $table = 'ai_decisions';

    protected $fillable = [
        'signal_id',
        'symbol',
        'timeframe',
        'action',
        'confidence',
        'reasoning',
        'prompt_used',
        'analysis_data',
        'ai_connection_id',
        'model_used',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'confidence' => 'integer',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }
}
