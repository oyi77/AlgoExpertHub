<?php

namespace Addons\TradingBotSignalAddon\App\Console\Commands;

use Addons\TradingBotSignalAddon\App\Services\FirebaseAuthService;
use Illuminate\Console\Command;

class RefreshFirebaseTokenCommand extends Command
{
    protected $signature = 'trading-bot:refresh-token';
    protected $description = 'Manually refresh Firebase access token';

    public function handle()
    {
        $this->info('Refreshing Firebase token...');
        
        $authService = new FirebaseAuthService();
        
        // Force refresh by calling getAccessToken which checks expiry
        $token = $authService->getAccessToken();
        
        if ($token) {
            $this->info('✅ Token refreshed successfully');
            $this->info('Token: ' . substr($token, 0, 50) . '...');
            return 0;
        } else {
            $this->error('❌ Failed to refresh token');
            return 1;
        }
    }
}

