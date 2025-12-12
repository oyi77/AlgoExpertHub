<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Models\Trade;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CryptoTradeController extends Controller
{

    public function index(Request $request)
    {
        $data['title'] = 'Trade';

        $data['trades'] = Trade::when($request->trx, function ($item) use ($request) {
            $item->where('ref', $request->trx);
        })->when($request->date, function ($item) use ($request) {
            $item->whereDate('trade_opens_at', $request->date);
        })->where('user_id', auth()->id())->orderBy('id', 'desc')->paginate(Helper::pagination());

        return view(Helper::themeView('user.trading'))->with($data);
    }

    public function latestTicker(Request $request)
    {
        try {
            $general = Helper::config();
            
            if (!$general) {
                return response()->json(['error' => 'Configuration not found', 'rate_limited' => false], 500);
            }

            $currency = $request->currency ?? 'BTC';
            $apiKey = $general->crypto_api ?? '';
            
            $url = "https://min-api.cryptocompare.com/data/v2/histominute?fsym={$currency}&tsym=USD&limit=40";
            if (!empty($apiKey)) {
                $url .= "&api_key=" . $apiKey;
            }
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($curlError) {
                \Log::error('CryptoCompare API error', ['error' => $curlError, 'currency' => $currency]);
                return response()->json(['error' => 'Connection error', 'rate_limited' => false, 'data' => []]);
            }

            // Check for rate limit (429 Too Many Requests)
            if ($httpCode === 429) {
                \Log::warning('CryptoCompare API rate limit hit', ['currency' => $currency]);
                return response()->json(['error' => 'Rate limit exceeded', 'rate_limited' => true, 'data' => []]);
            }

            if ($httpCode !== 200) {
                \Log::error('CryptoCompare API HTTP error', ['code' => $httpCode, 'currency' => $currency]);
                return response()->json(['error' => 'API error', 'rate_limited' => false, 'data' => []]);
            }

            $result = json_decode($response, true);

            // Check if response is valid
            if (!$result) {
                return response()->json(['error' => 'Invalid response', 'rate_limited' => false, 'data' => []]);
            }

            // Check for API error response (rate limit or other errors)
            if (isset($result['Response']) && $result['Response'] === 'Error') {
                $isRateLimited = isset($result['Message']) && (
                    stripos($result['Message'], 'rate limit') !== false ||
                    stripos($result['Message'], 'too many') !== false ||
                    stripos($result['Message'], '429') !== false
                );
                
                \Log::warning('CryptoCompare API error response', [
                    'message' => $result['Message'] ?? 'Unknown error',
                    'currency' => $currency,
                    'rate_limited' => $isRateLimited
                ]);
                
                return response()->json([
                    'error' => $result['Message'] ?? 'API error',
                    'rate_limited' => $isRateLimited,
                    'data' => []
                ]);
            }

            if (!isset($result['Data']) || !isset($result['Data']['Data'])) {
                return response()->json(['error' => 'No data available', 'rate_limited' => false, 'data' => []]);
            }

            $hvoc = $result['Data']['Data'];

            if (!is_array($hvoc) || empty($hvoc)) {
                return response()->json(['error' => 'Empty data', 'rate_limited' => false, 'data' => []]);
            }

            $chartData = [];

            foreach ($hvoc as $key => $value) {
                if (isset($value['time']) && isset($value['open']) && isset($value['high']) && isset($value['low']) && isset($value['close'])) {
                    $chartData[] = [
                        'x' => $value['time'] * 1000, // Convert to milliseconds for ApexCharts
                        'y' => [(float)$value['open'], (float)$value['high'], (float)$value['low'], (float)$value['close']]
                    ];
                }
            }

            // Return empty array if no valid data points
            if (empty($chartData)) {
                return response()->json(['error' => 'No valid data points', 'rate_limited' => false, 'data' => []]);
            }

            return response()->json($chartData);
        } catch (\Exception $e) {
            \Log::error('latestTicker error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Server error', 'rate_limited' => false, 'data' => []]);
        }
    }

    public function currentPrice(Request $request)
    {
        try {
            $general = Helper::config();

            if (!$general) {
                \Log::error('Configuration not found in currentPrice');
                return response()->json(['error' => 'Configuration not found', 'rate_limited' => false], 200);
            }

            $currency = $request->currency ?? 'BTC';
            $apiKey = $general->crypto_api ?? '';

            $url = "https://min-api.cryptocompare.com/data/price?fsym={$currency}&tsyms=USD";
            if (!empty($apiKey)) {
                $url .= "&api_key=" . $apiKey;
            }
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                \Log::error('CryptoCompare price fetch failed', ['currency' => $currency]);
                return response()->json(['error' => 'Failed to fetch price', 'rate_limited' => false], 200);
            }

            $data = json_decode($response, true);

            if (!$data || !is_array($data)) {
                \Log::error('CryptoCompare invalid response', ['currency' => $currency, 'response' => $response]);
                return response()->json(['error' => 'Invalid API response', 'rate_limited' => false], 200);
            }

            // Check for API error response
            if (isset($data['Response']) && $data['Response'] === 'Error') {
                $isRateLimited = isset($data['Message']) && (
                    stripos($data['Message'], 'rate limit') !== false ||
                    stripos($data['Message'], 'too many') !== false ||
                    stripos($data['Message'], '429') !== false
                );
                
                \Log::warning('CryptoCompare API error', [
                    'currency' => $currency,
                    'message' => $data['Message'] ?? 'Unknown error',
                    'rate_limited' => $isRateLimited
                ]);
                
                return response()->json([
                    'error' => $data['Message'] ?? 'API error',
                    'rate_limited' => $isRateLimited
                ], 200);
            }

            $result = reset($data);

            if ($result === false || $result === null || !is_numeric($result)) {
                \Log::error('CryptoCompare no price data', ['currency' => $currency, 'data' => $data]);
                return response()->json(['error' => 'No price data available', 'rate_limited' => false], 200);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('currentPrice error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'currency' => $request->currency ?? 'BTC']);
            return response()->json(['error' => 'Server error: ' . $e->getMessage(), 'rate_limited' => false], 200);
        }
    }

    public function trades()
    {
        $data['trades'] = Trade::where('user_id', auth()->id())->paginate(Helper::pagination());

        $data['title'] = 'Trades List';

        return view(Helper::themeView('user.trade_list'))->with($data);
    }


    public function openTrade(Request $request)
    {
        $request->validate([
            "trade_cur" => "required",
            "trade_price" => "required",
            "type" => "required|in:buy,sell",
            "duration" => "required|gt:0"
        ]);

        $user = auth()->user();


        if ($user->trades->count() >= Helper::config()->trade_limit) {
            return redirect()->back()->with('error', 'Per Day Trading Limit expired');
        }

        if ($user->payments->count() <= 0) {
            return redirect()->back()->with('error', 'You need to subscribe a plan to trade');
        }



        if ($user->balance < Helper::config()->min_trade_balance) {
            return redirect()->back()->with('error', 'You need minimum of ' . Helper::formatter(Helper::config()->min_trade_balance) . ' To Trade');
        }


        $ref = Str::random(16);

        Trade::create([
            'ref' => $ref,
            'user_id' => auth()->id(),
            'currency' => $request->trade_cur,
            'current_price' => $request->trade_price,
            'trade_type' => $request->type,
            'duration' => $request->duration,
            'trade_stop_at' => now()->addMinutes($request->duration),
            'trade_opens_at' => now()
        ]);

        return redirect()->back()->with('success', 'Trade Open Successfully');
    }

    public function tradeClose()
    {
        $config = Helper::config();

        $trades = Trade::where('user_id', auth()->id())->where('status', 0)->get();



        foreach ($trades as  $trade) {

            if ($trade->trade_stop_at->lte(now())) {

                $data = json_decode(file_get_contents("https://min-api.cryptocompare.com/data/price?fsym={$trade->currency}&tsyms=USD&api_key=" . $config->crypto_api), true);

                $currentPrice = reset($data);

                if ($currentPrice > $trade->current_price) {

                    // calculations
                    $amount = $currentPrice - $trade->current_price;
                    $charge = ($config->trade_charge / 100) * $amount;
                    $userAmount = $amount - $charge;
                    $type = '+';


                    // Trading Part 
                    $trade->profit_type = $type;
                    $trade->profit_amount = $amount;
                    $trade->charge = $charge;
                    $trade->status = 1;


                    // User Part
                    $trade->user->balance += $userAmount;
                    $trade->user->save();
                } else {

                    // calculations
                    $amount = $trade->current_price - $currentPrice;
                    $charge = 0;
                    $userAmount = $amount;
                    $type = '-';

                    // Trading Part 
                    $trade->profit_type = $type;
                    $trade->loss_amount = $amount;
                    $trade->charge = 0;
                    $trade->status = 1;

                    // User Part
                    $trade->user->balance -= $userAmount;
                    $trade->user->save();
                }

                $trade->save();

                Transaction::create([
                    'trx' => $trade->ref,
                    'amount' => $amount,
                    'details' => 'Trade Return',
                    'charge' => $charge,
                    'type' => $type,
                    'user_id' => $trade->user->id
                ]);
            }
        }
    }


    public function tradingInterest()
    {

        $config = Helper::config();

        $trades = Trade::where('status', 0)->get();

        foreach ($trades as  $trade) {

            if ($trade->trade_stop_at->lte(now())) {

                $data = json_decode(file_get_contents("https://min-api.cryptocompare.com/data/price?fsym={$trade->currency}&tsyms=USD&api_key=" . $config->crypto_api), true);

                $currentPrice = reset($data);

                if ($currentPrice > $trade->current_price) {

                    // calculations
                    $amount = $currentPrice - $trade->current_price;
                    $charge = ($config->trade_charge / 100) * $amount;
                    $userAmount = $amount - $charge;
                    $type = '+';


                    // Trading Part 
                    $trade->profit_type = $type;
                    $trade->profit_amount = $amount;
                    $trade->charge = $charge;
                    $trade->status = 1;


                    // User Part
                    $trade->user->balance += $userAmount;
                    $trade->user->save();
                } else {

                    // calculations
                    $amount = $trade->current_price - $currentPrice;
                    $charge = 0;
                    $userAmount = $amount;
                    $type = '-';

                    // Trading Part 
                    $trade->profit_type = $type;
                    $trade->loss_amount = $amount;
                    $trade->charge = 0;
                    $trade->status = 1;

                    // User Part
                    $trade->user->balance -= $userAmount;
                    $trade->user->save();
                }

                $trade->save();

                Transaction::create([
                    'trx' => $trade->ref,
                    'amount' => $amount,
                    'details' => 'Trade Return',
                    'charge' => $charge,
                    'type' => $type,
                    'user_id' => $trade->user->id
                ]);
            }
        }
    }

    /**
     * Server-Sent Events endpoint for real-time price updates
     */
    public function streamPrices(Request $request)
    {
        $currency = $request->get('currency', 'BTC');
        
        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Set headers to force HTTP/1.1 and disable QUIC
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('HTTP/1.1 200 OK');
        
        // Disable time limit for long-running connection
        set_time_limit(0);
        ignore_user_abort(false);
        
        $general = Helper::config();
        $apiKey = $general->crypto_api ?? '';
        
        // Send initial connection message
        echo "data: " . json_encode(['type' => 'connected', 'currency' => $currency]) . "\n\n";
        flush();
        
        $lastChartData = null;
        $lastPrice = null;
        $updateCount = 0;
        
        while (true) {
            if (connection_aborted()) {
                break;
            }
            
            // Send keepalive every 30 seconds
            if ($updateCount % 10 == 0 && $updateCount > 0) {
                echo ": keepalive\n\n";
                flush();
            }
            
            try {
                // Fetch chart data
                $url = "https://min-api.cryptocompare.com/data/v2/histominute?fsym={$currency}&tsym=USD&limit=40";
                if (!empty($apiKey)) {
                    $url .= "&api_key=" . $apiKey;
                }
                
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_FOLLOWLOCATION => true,
                ]);
                
                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    
                    if ($result && isset($result['Response']) && $result['Response'] === 'Success' && isset($result['Data']['Data'])) {
                        $chartData = [];
                        foreach ($result['Data']['Data'] as $value) {
                            if (isset($value['time'], $value['open'], $value['high'], $value['low'], $value['close'])) {
                                $chartData[] = [
                                    'x' => $value['time'] * 1000,
                                    'y' => [(float)$value['open'], (float)$value['high'], (float)$value['low'], (float)$value['close']]
                                ];
                            }
                        }
                        
                        // Only send if data changed
                        $chartDataJson = json_encode($chartData);
                        if ($lastChartData !== $chartDataJson) {
                            echo "data: " . json_encode(['type' => 'chart', 'data' => $chartData]) . "\n\n";
                            flush();
                            $lastChartData = $chartDataJson;
                        }
                    }
                }
                
                // Fetch current price
                $priceUrl = "https://min-api.cryptocompare.com/data/price?fsym={$currency}&tsyms=USD";
                if (!empty($apiKey)) {
                    $priceUrl .= "&api_key=" . $apiKey;
                }
                
                $priceResponse = @file_get_contents($priceUrl, false, stream_context_create([
                    'http' => ['timeout' => 5, 'ignore_errors' => true]
                ]));
                
                if ($priceResponse !== false) {
                    $priceData = json_decode($priceResponse, true);
                    if ($priceData && !isset($priceData['Response']) && is_array($priceData)) {
                        $currentPrice = reset($priceData);
                        if ($currentPrice !== false && $currentPrice !== null && $lastPrice != $currentPrice) {
                            echo "data: " . json_encode(['type' => 'price', 'price' => $currentPrice, 'currency' => $currency]) . "\n\n";
                            flush();
                            $lastPrice = $currentPrice;
                        }
                    }
                }
                
            } catch (\Exception $e) {
                \Log::error('SSE stream error', ['error' => $e->getMessage()]);
            }
            
            $updateCount++;
            
            // Wait 3 seconds before next update
            sleep(3);
        }
        
        // This should never be reached, but just in case
        return response('', 200);
    }
}
