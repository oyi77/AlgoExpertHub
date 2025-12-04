<?php

namespace App\Jobs;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use App\Models\DashboardSignal;
use App\Models\Signal;
use App\Models\Template;
use App\Models\User;
use App\Models\UserSignal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendChannelMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $channel;
    public int $userId;
    public int $signalId;
    public int $planId;

    public function __construct(string $channel, int $userId, int $signalId, int $planId)
    {
        $this->channel = $channel;
        $this->userId = $userId;
        $this->signalId = $signalId;
        $this->planId = $planId;
        $this->onQueue('notifications');
        $this->tries = 3;
        $this->backoff = [5, 30, 120];
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $signal = Signal::with(['market', 'pair', 'time'])->find($this->signalId);
        if (!$user || !$signal) return;

        if ($this->channel === 'dashboard') {
            DB::table('dashboard_signals')->insertOrIgnore([
                'user_id' => $user->id,
                'plan_id' => $this->planId,
                'signal_id' => $signal->id,
            ]);
        } elseif ($this->channel === 'email') {
            $general = Helper::config();
            $data = [
                'app_name' => $general->appname,
                'email' => $user->email,
                'username' => $user->username,
                'title' => $signal->title,
                'market' => $signal->market->name,
                'pair' => $signal->pair->name,
                'frame' => $signal->time->name,
                'open' => $signal->open_price,
                'sl' => $signal->sl,
                'tp' => $signal->tp,
                'direction' => $signal->direction,
                'description' => clean($signal->description),
            ];
            $template = Template::where('name', 'signal')->where('status', 1)->first();
            if ($template) {
                Helper::fireMail($data, $template);
            }
        } elseif ($this->channel === 'sms') {
            try {
                $basic  = new \Nexmo\Client\Credentials\Basic(env('NEXMO_KEY'), env('NEXMO_SECRET'));
                $client = new \Nexmo\Client($basic);
                $client->message()->send([
                    'to' => $user->phone,
                    'from' => config('app.name'),
                    'text' => sprintf(
                        'Title:%s Market:%s Pair:%s TF:%s Open:%s SL:%s TP:%s Dir:%s',
                        $signal->title,
                        $signal->market->name,
                        $signal->pair->name,
                        $signal->time->name,
                        $signal->open_price,
                        $signal->sl,
                        $signal->tp,
                        $signal->direction
                    ),
                ]);
            } catch (\Throwable $e) {}
        } elseif ($this->channel === 'whatsapp') {
            try {
                $params = [
                    'token' => env('ULTRA_TOKEN'),
                    'to' => $user->phone,
                    'body' => sprintf(
                        "Title:%s\nMarket:%s\nPair:%s\nTF:%s\nOpen:%s\nSL:%s\nTP:%s\nDir:%s",
                        $signal->title,
                        $signal->market->name,
                        $signal->pair->name,
                        $signal->time->name,
                        $signal->open_price,
                        $signal->sl,
                        $signal->tp,
                        $signal->direction
                    ),
                ];
                $id = env('ULTRA_ID');
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.ultramsg.com/{$id}/messages/chat",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($params),
                    CURLOPT_HTTPHEADER => ['content-type: application/x-www-form-urlencoded'],
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ]);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Throwable $e) {}
        } elseif ($this->channel === 'telegram') {
            try {
                $general = Helper::config();
                $message = sprintf(
                    'Title:%s\nMarket:%s\nPair:%s\nTF:%s\nOpen:%s\nSL:%s\nTP:%s\nDir:%s',
                    $signal->title,
                    $signal->market->name,
                    $signal->pair->name,
                    $signal->time->name,
                    $signal->open_price,
                    $signal->sl,
                    $signal->tp,
                    $signal->direction
                );
                if (!empty($user->telegram_chat_id)) {
                    $web = 'https://api.telegram.org/bot' . $general->telegram_token;
                    $url = $web . '/sendMessage?chat_id=' . $user->telegram_chat_id . '&text=' . urlencode($message);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            } catch (\Throwable $e) {}
        }

        DB::table('user_signals')->insertOrIgnore([
            'user_id' => $this->userId,
            'signal_id' => $this->signalId,
        ]);
    }
}
