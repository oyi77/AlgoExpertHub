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

        return view(Helper::theme() . 'user.trading')->with($data);
    }

    public function latestTicker(Request $request)
    {
        try {
            $general = Helper::config();
            
            if (!$general) {
                return response()->json(['error' => 'Configuration not found'], 500);
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
                return response()->json([]);
            }

            if ($httpCode !== 200) {
                \Log::error('CryptoCompare API HTTP error', ['code' => $httpCode, 'currency' => $currency]);
                return response()->json([]);
            }

            $result = json_decode($response, true);

            // Check if response is valid
            if (!$result || !isset($result['Response']) || $result['Response'] !== 'Success') {
                // If API returns error, return empty array instead of error
                return response()->json([]);
            }

            if (!isset($result['Data']) || !isset($result['Data']['Data'])) {
                return response()->json([]);
            }

            $hvoc = $result['Data']['Data'];

            if (!is_array($hvoc)) {
                return response()->json([]);
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

            return response()->json($chartData);
        } catch (\Exception $e) {
            \Log::error('latestTicker error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([]);
        }
    }

    public function currentPrice(Request $request)
    {
        try {
            $general = Helper::config();

            if (!$general) {
                \Log::error('Configuration not found in currentPrice');
                return response()->json(['error' => 'Configuration not found'], 200);
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
                return response()->json(['error' => 'Failed to fetch price'], 200);
            }

            $data = json_decode($response, true);

            if (!$data || !is_array($data)) {
                \Log::error('CryptoCompare invalid response', ['currency' => $currency, 'response' => $response]);
                return response()->json(['error' => 'Invalid API response'], 200);
            }

            // Check for API error response
            if (isset($data['Response']) && $data['Response'] === 'Error') {
                \Log::error('CryptoCompare API error', ['currency' => $currency, 'message' => $data['Message'] ?? 'Unknown error']);
                return response()->json(['error' => $data['Message'] ?? 'API error'], 200);
            }

            $result = reset($data);

            if ($result === false || $result === null) {
                \Log::error('CryptoCompare no price data', ['currency' => $currency, 'data' => $data]);
                return response()->json(['error' => 'No price data available'], 200);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('currentPrice error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'currency' => $request->currency ?? 'BTC']);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 200);
        }
    }

    public function trades()
    {
        $data['trades'] = Trade::where('user_id', auth()->id())->paginate(Helper::pagination());

        $data['title'] = 'Trades List';

        return view(Helper::theme() . 'user.trade_list')->with($data);
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
}
