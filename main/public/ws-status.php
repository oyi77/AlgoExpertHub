<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Status Check</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .ok { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 WebSocket System Status</h1>
    
    <?php
    // Check Soketi
    echo '<h2>1. Soketi Server</h2>';
    $soketi = @file_get_contents('http://172.18.0.2:6001/');
    if ($soketi !== false) {
        echo '<div class="status ok">✅ Soketi is running on 172.18.0.2:6001</div>';
    } else {
        echo '<div class="status error">❌ Cannot connect to Soketi</div>';
    }
    
    // Check config
    echo '<h2>2. Laravel Configuration</h2>';
    echo '<pre>';
    echo "BROADCAST_DRIVER: " . getenv('BROADCAST_DRIVER') . "\n";
    echo "PUSHER_HOST: " . getenv('PUSHER_HOST') . "\n";
    echo "PUSHER_PORT: " . getenv('PUSHER_PORT') . "\n";
    echo "PUSHER_APP_KEY: " . getenv('PUSHER_APP_KEY') . "\n";
    echo '</pre>';
    
    // Check Pusher class
    echo '<h2>3. Pusher PHP SDK</h2>';
    if (class_exists('Pusher\Pusher')) {
        echo '<div class="status ok">✅ Pusher\Pusher class is loaded</div>';
    } else {
        echo '<div class="status error">❌ Pusher\Pusher class NOT found</div>';
    }
    
    // Instructions
    echo '<h2>4. Frontend Test</h2>';
    echo '<div class="status info">';
    echo '<p><strong>Open browser console on trading bot page and check for:</strong></p>';
    echo '<ul>';
    echo '<li>✅ WebSocket connected to Soketi</li>';
    echo '<li>✅ WebSocket: Connected to admin.trading-bot.X</li>';
    echo '<li>❌ If you see "WebSocket not available", Echo is not initialized</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<h2>5. Test Links</h2>';
    echo '<ul>';
    echo '<li><a href="/websocket-test.html" target="_blank">WebSocket Test Page</a></li>';
    echo '<li><a href="http://' . $_SERVER['HTTP_HOST'] . ':9601" target="_blank">Soketi Dashboard</a></li>';
    echo '<li><a href="/admin/trading-management/trading-bots/1" target="_blank">Trading Bot Page</a></li>';
    echo '</ul>';
    ?>
    
    <h2>6. Manual Test</h2>
    <p>Run this command to broadcast a test event:</p>
    <pre>docker exec 1Panel-php8-mrTy php /www/sites/aitradepulse.com/index/main/artisan tinker --execute="
\$bot = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::first();
\$service = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotBroadcastService::class);
\$service->broadcastStatusChange(\$bot, 'running', 'Test from CLI - ' . date('H:i:s'));
"</pre>
</body>
</html>

