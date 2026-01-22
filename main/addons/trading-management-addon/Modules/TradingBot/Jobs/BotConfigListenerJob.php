<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\TradingBot\Jobs;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class BotConfigListenerJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;
    
    public $maxExceptions = 3;
    public $timeout = 3600;
    
    public function __construct(
        public int $botId,
        public string $channel = 'subscribe'
    ) {}
    
    public function handle(): void
    {
        if ($this->channel === 'unsubscribe') {
            return;
        }
        
        $bot = TradingBot::find($this->botId);
        if (!$bot || !in_array($bot->status, ['running', 'paused'])) {
            return;
        }
        
        try {
            Redis::subscribe(
                ["bot:{$this->botId}:config"],
                function ($message) {
                    $data = json_decode($message, true);
                    return ($data['event'] ?? null) === 'config_updated'
                        && isset($data['config'])
                        && isset($data['timestamp']);
                }
            );
        } catch (\Exception $e) {
            Log::error('Redis subscription error', [
                'bot_id' => $this->botId,
                'error' => $e->getMessage(),
            ]);
            
            $this->release(5);
            
            if (app()->environment('testing')) {
                sleep(5);
            }
            
            $this->release(5);
        }
    }
    
    public function stopListening(TradingBot $bot): void
    {
        Redis::unsubscribe(["bot:{$bot->id}:config"]);
        Log::info('Bot config listener stopped', ['bot_id' => $bot->id]);
    }
}
