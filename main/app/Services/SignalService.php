<?php

namespace App\Services;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use App\Models\DashboardSignal;
use App\Models\Signal;
use App\Models\Template;
use App\Models\UserSignal;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

use NotificationChannels\Telegram\TelegramUpdates;

class SignalService
{
    public function create($request)
    {
        $description =  clean($request->description, 'youtube');


        if ($request->description) {

            $content = $request->description;

            $dom = new \DomDocument();
            $dom->loadHtml($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $imageFile = $dom->getElementsByTagName('img');
            foreach ($imageFile as $item => $image) {

                if ($image->getAttribute('data-filename') == null) {
                    continue;
                }

                if ($image->getAttribute('src') == '') {
                    continue;
                }

                $imageSrc = $image->getAttribute('src');


                $str = base64_encode($imageSrc);


                if (!$str) {
                    continue;
                }

                list($type, $imageSrc) = explode(';', $imageSrc);
                list(, $imageSrc)      = explode(',', $imageSrc);
                $imgeData = base64_decode($imageSrc);

                $image_name = time() . $item . '.png';

                $path = Helper::filePath('summernote', true) . '/' . $image_name;

                if (!file_exists(Helper::filePath('summernote', true))) {

                    mkdir(Helper::filePath('summernote', true), 0755, true);
                }

                file_put_contents($path, $imgeData);

                $image->removeAttribute('src');
                $image->setAttribute('src', Helper::getFile('summernote', $image_name, true));
                $image->setAttribute('data-old', 'yes');
            }

            $description = $dom->saveHTML();
        }


        $signal = Signal::create([
            'title' => $request->title,
            'currency_pair_id' => $request->currency_pair,
            'time_frame_id' => $request->time_frame,
            'open_price' => $request->open_price,
            'sl' => $request->sl,
            'tp' => $request->tp ?? 0, // Primary TP (fallback for backward compatibility)
            'image' => $request->has('image') ? Helper::saveImage($request->image, Helper::filePath('signal', true)) : '',
            'description' => $description,
            'direction' => $request->direction,
            'market_id' => $request->market,
            'is_published' => 0
        ]);

        // Handle multiple TPs if provided
        if ($request->has('take_profits') && is_array($request->take_profits)) {
            foreach ($request->take_profits as $index => $tpData) {
                if (isset($tpData['tp_price']) && $tpData['tp_price'] > 0) {
                    \App\Models\SignalTakeProfit::create([
                        'signal_id' => $signal->id,
                        'tp_level' => $index + 1,
                        'tp_price' => $tpData['tp_price'],
                        'tp_percentage' => $tpData['tp_percentage'] ?? null,
                        'lot_percentage' => $tpData['lot_percentage'] ?? null,
                    ]);
                }
            }
            
            // Update primary TP to first level if multiple TPs exist
            $firstTp = $signal->takeProfits()->orderBy('tp_level')->first();
            if ($firstTp) {
                $signal->update(['tp' => $firstTp->tp_price]);
            }
        }

        $signal->plans()->attach($request->plans);


        if ($request->type === 'Send') {

            $this->sent($signal->id);

            $signal->published_date = now();

            $signal->save();
        }

        return ['type' => 'success', 'message' => 'Signal Created Successfull'];
    }

    public function update($request, $id)
    {

        error_reporting(E_ERROR | E_PARSE);

        $signal = Signal::find($id);

        if (!$signal) {
            return ['type' => 'error', 'message' => 'No Signals Found'];
        }

        $description =  clean($request->description, 'youtube');

        if ($request->description) {

            $content = $request->description;

            $dom = new \DomDocument();
            $dom->loadHtml($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $imageFile = $dom->getElementsByTagName('img');
            foreach ($imageFile as $item => $image) {

                if ($image->getAttribute('data-filename') == null) {
                    continue;
                }

                if ($image->getAttribute('src') == '') {
                    continue;
                }

                $imageSrc = $image->getAttribute('src');


                $str = base64_encode($imageSrc);


                if (!$str) {
                    continue;
                }

                list($type, $imageSrc) = explode(';', $imageSrc);
                list(, $imageSrc)      = explode(',', $imageSrc);
                $imgeData = base64_decode($imageSrc);

                $image_name = time() . $item . '.png';

                $path = Helper::filePath('summernote', true) . '/' . $image_name;

                if (!file_exists(Helper::filePath('summernote', true))) {

                    mkdir(Helper::filePath('summernote', true), 0755, true);
                }

                file_put_contents($path, $imgeData);

                $image->removeAttribute('src');
                $image->setAttribute('src', Helper::getFile('summernote', $image_name, true));
                $image->setAttribute('data-old', 'yes');
            }

            $description = $dom->saveHTML();
        }

        // Store original data for modification detection
        $originalData = [
            'sl' => $signal->sl,
            'tp' => $signal->tp,
            'open_price' => $signal->open_price,
            'take_profits' => $signal->takeProfits()->orderBy('tp_level')->get()->pluck('tp_price', 'tp_level')->toArray(),
        ];

        $signal->update([
            'title' => $request->title,
            'currency_pair_id' => $request->currency_pair,
            'time_frame_id' => $request->time_frame,
            'open_price' => $request->open_price,
            'sl' => $request->sl,
            'tp' => $request->tp ?? 0, // Primary TP (fallback)
            'image' => $request->has('image') ? Helper::saveImage($request->image, Helper::filePath('signal', true), '', $signal->image) : $signal->image,
            'description' => $description,
            'direction' => $request->direction,
            'market_id' => $request->market
        ]);

        // Handle multiple TPs if provided
        if ($request->has('take_profits') && is_array($request->take_profits)) {
            // Delete existing TPs
            $signal->takeProfits()->delete();
            
            // Create new TPs
            foreach ($request->take_profits as $index => $tpData) {
                if (isset($tpData['tp_price']) && $tpData['tp_price'] > 0) {
                    \App\Models\SignalTakeProfit::create([
                        'signal_id' => $signal->id,
                        'tp_level' => $index + 1,
                        'tp_price' => $tpData['tp_price'],
                        'tp_percentage' => $tpData['tp_percentage'] ?? null,
                        'lot_percentage' => $tpData['lot_percentage'] ?? null,
                    ]);
                }
            }
            
            // Update primary TP to first level if multiple TPs exist
            $firstTp = $signal->takeProfits()->orderBy('tp_level')->first();
            if ($firstTp) {
                $signal->update(['tp' => $firstTp->tp_price]);
            }
        }

        $signal->plans()->sync($request->plans);

        // Handle signal modification if signal is published
        if ($signal->is_published) {
            try {
                $modificationService = app(\App\Services\SignalModificationService::class);
                $modificationService->handleSignalModification($signal->fresh(), $originalData);
            } catch (\Exception $e) {
                \Log::error('Failed to handle signal modification', [
                    'signal_id' => $signal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->type == 'Send') {




            $this->sent($signal->id);



            $signal->published_date = now();

            $signal->save();
        }

        return ['type' => 'success', 'message' => 'Signal Updated Successfully'];
    }


    public function destroy($id)
    {
        $signal = Signal::find($id);

        if (!$signal) {
            return ['type' => 'error', 'message' => 'No Signals Found'];
        }

        $planIds = $signal->plans()->pluck('plan_id')->toArray();

        $signal->plans()->detach($planIds);

        $image = Helper::filePath('signal', true) . '/' . $signal->image;

        if ($signal->image != null) {
            if (file_exists($image)) {
                unlink($image);
            }
        }

        $signal->delete();

        return ['type' => 'success', 'message' => 'Successfully Deleted Signal'];
    }



    public function sent($id)
    {

        $signal = Signal::find($id);

        if (!$signal) {
            return ['type' => 'error', 'message' => 'No Signals Found'];
        }

        $signal->is_published = 1;
        $signal->published_date = now();
        $signal->save();
        \App\Jobs\DistributeSignalJob::dispatch($signal->id)->onQueue('notifications');

        return ['type' => 'success', 'message' => 'Successfully sent Signal'];
    }


    public function sendSignalToUser($signal)
    {

        $general = Helper::config();

        $plans = $signal->plans()->where('status', 1)->with('subscriptions')->get();


        foreach ($plans as $plan) {
            $subscriptions = $plan->subscriptions()->where('is_current', 1)->with('user')->get();


            if ($subscriptions->isNotEmpty()) {
                foreach ($subscriptions as $subscription) {

                    $isNotExpired = $subscription->plan_expired_at->gt(now());


                    if ($isNotExpired) {

                        // Distribution moved to queue; keep minimal persistence here if needed
                        \App\Jobs\SendChannelMessageJob::dispatch('dashboard', $subscription->user->id, $signal->id, $plan->id)->onQueue('notifications');
                        if ($plan->whatsapp) {
                            \App\Jobs\SendChannelMessageJob::dispatch('whatsapp', $subscription->user->id, $signal->id, $plan->id)->onQueue('notifications');
                        }
                        if ($plan->telegram) {
                            \App\Jobs\SendChannelMessageJob::dispatch('telegram', $subscription->user->id, $signal->id, $plan->id)->onQueue('notifications');
                        }
                        if ($plan->email) {
                            \App\Jobs\SendChannelMessageJob::dispatch('email', $subscription->user->id, $signal->id, $plan->id)->onQueue('notifications');
                        }
                        if ($plan->sms) {
                            \App\Jobs\SendChannelMessageJob::dispatch('sms', $subscription->user->id, $signal->id, $plan->id)->onQueue('notifications');
                        }
                    }
                }
            }
        }
    }


    private function sendText($signal, $user)
    {

        $general = Helper::config();

        $message = '';
        $message .= 'Title : ' . $signal->title . '\n';
        $message .= 'market : ' . $signal->market->name . '\n';
        $message .= 'pair : ' . $signal->pair->name . '\n';
        $message .= 'frame : ' . $signal->time->name . '\n';
        $message .= 'open : ' . $signal->open_price . '\n';
        $message .= 'sl : ' . $signal->sl . '\n';
        $message .= 'tp : ' . $signal->tp . '\n';
        $message .= 'direction : ' . $signal->direction;

        try {
            $basic  = new \Nexmo\Client\Credentials\Basic(env("NEXMO_KEY"), env("NEXMO_SECRET"));
            $client = new \Nexmo\Client($basic);
            $client->message()->send([
                'to' => $user->phone,
                'from' => $general->appname,
                'text' => strip_tags($message)
            ]);
        } catch (\Throwable $e) {}
    }


    private static function telegramSend($signal, $user)
    {
        $general = Configuration::first();

        if ($general->allow_telegram == 1) {

            $message = '';
            $message .= 'Title : ' . $signal->title . '               ';
            $message .= 'market : ' . $signal->market->name . '               ';
            $message .= 'pair : ' . $signal->pair->name . '               ';
            $message .= 'frame : ' . $signal->time->name . '              ';
            $message .= 'open : ' . $signal->open_price . '               ';
            $message .= 'sl : ' . $signal->sl . '             ';
            $message .= 'tp : ' . $signal->tp . '             ';
            $message .= 'direction : ' . $signal->direction . '               ';

            // Prefer chat_id stored per user via webhook; if missing, no-op here
            if (property_exists($user, 'telegram_chat_id') && $user->telegram_chat_id) {
                $web = 'https://api.telegram.org/bot' . $general->telegram_token;
                $url = $web . "/sendMessage?chat_id=" . $user->telegram_chat_id . "&text=" . urlencode($message);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }

        }
    }

    private static function whatsappSend($signal, $user)
    {

        if (env('ALLOW_ULTRA') == 'on') {

            $calling_code = rtrim(file_get_contents('https://ipapi.co/103.100.232.0/country_calling_code/'), "0");

            $receiverNumber = $calling_code . $user->phone;


            $message = '';
            $message .= 'Title : ' . $signal->title . '\n';
            $message .= 'market : ' . $signal->market->name . '\n';
            $message .= 'pair : ' . $signal->pair->name . '\n';
            $message .= 'frame : ' . $signal->time->name . '\n';
            $message .= 'open : ' . $signal->open_price . '\n';
            $message .= 'sl : ' . $signal->sl . '\n';
            $message .= 'tp : ' . $signal->tp . '\n';
            $message .= 'direction : ' . $signal->direction;

            $params = array(
                'token' => env('ULTRA_TOKEN'),
                'to' => $receiverNumber,
                'body' => $message
            );
            $curl = curl_init();

            $id = env('ULTRA_ID');


            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ultramsg.com/{$id}/messages/chat",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/x-www-form-urlencoded"
                ),
            ));

            curl_exec($curl);
        }
    }
}
