<?php

namespace App\Services;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;

class ConfigurationService
{

    public function general($request)
    {

        if ($request->type == 'general') {

            if ($request->has('logo')) {

                $logo = 'logo' . '.' . $request->logo->getClientOriginalExtension();

                $request->logo->move(Helper::filePath('logo'), $logo);
            }

            if ($request->has('icon')) {

                $icon = 'icon' . '.' . $request->icon->getClientOriginalExtension();

                $request->icon->move(Helper::filePath('icon'), $icon);
            }


            if ($request->has('dark_logo')) {

                $dark_logo = 'dark_logo' . '.' . $request->dark_logo->getClientOriginalExtension();

                $request->dark_logo->move(Helper::filePath('dark_logo'), $dark_logo);
            }


            $existingConfig = Configuration::first();
            
            Configuration::updateOrCreate([
                'id' => 1
            ], [
                'appname' => $request->sitename,
                'signup_bonus' => $request->signup_bonus,
                'currency' => $request->site_currency,
                'logo' => isset($logo) ? ($logo ?? '') : ($existingConfig->logo ?? ''),
                'favicon' => isset($icon) ? ($icon ?? '') : ($existingConfig->favicon ?? ''),
                'dark_logo' => isset($dark_logo) ? ($dark_logo ?? '') : ($existingConfig->dark_logo ?? ''),
                'color' =>  $request->primary_color ?? '',
                'withdraw_limit' => $request->withdraw_limit,
                'pagination' => $request->pagination_limit,
                'alert' => $request->alert,
                'transfer_type' => $request->trans_type,
                'transfer_limit' => $request->trans_limit,
                'transfer_charge' => $request->trans_charge,
                'transfer_min_amount' => $request->min_amount,
                'transfer_max_amount' => $request->max_amount,
                'decimal_precision' => $request->decimal_precision,
                'trade_charge' => $request->trade_charge,
                'min_trade_balance' => $request->min_trade_balance,
                'trade_limit' => $request->trade_limit
            ]);

            Helper::setEnv([
                'APP_TIMEZONE' => $request->timezone,
                'MAIL_FROM_NAME' => $request->sitename
            ]);

            Artisan::call('config:clear');
        } elseif ($request->type == 'api') {

            Configuration::updateOrCreate([
                'id' => 1
            ], [
                'recaptcha_key' => $request->recaptcha_key,
                'recaptcha_secret' => $request->recaptcha_secret,
                'allow_recaptcha' => $request->allow_recaptcha == 'on' ? 1 : 0,

                'bot_url' => $request->bot_url,
                'telegram_token' => $request->telegram_token,
                'allow_telegram' => $request->allow_telegram == 'on' ? 1 : 0,

                'tdio_url' => $request->tidio_url,
                'tdio_allow' => $request->tdio_allow === 'on' ? 1 : 0,

                'analytics_key' => $request->analytics_key,
                'analytics_status' => $request->analytics_status === 'on' ? 1 : 0,

                'allow_facebook' => $request->allow_facebook === 'on' ? 1 : 0,
                'allow_google' => $request->allow_google === 'on' ? 1 : 0,

                'crypto_api' => $request->api_key,
            ]);

            Helper::setEnv([
                'NEXMO_KEY' => $request->sms_username,
                'NEXMO_SECRET' => $request->sms_password,
                'FACEBOOK_APP_ID' => $request->app_id,
                'FACEBOOK_SECRET' => $request->app_secret,
                'GOOGLE_APP_ID' => $request->google_app_id,
                'GOOGLE_SECRET' => $request->google_app_secret,
                'ULTRA_ID' => $request->ultra_id,
                'ULTRA_TOKEN' => $request->ultra_token,
                'ULTRA_FROM' => $request->ultra_from,
                'ALLOW_ULTRA' => $request->allow_ultra
            ]);
        } elseif ($request->type == 'others') {
            Configuration::updateOrCreate([
                'id' => 1
            ], [
                'button_text' => $request->button_text,
                'cookie_text' => $request->cookie_text,
                'allow_modal' => $request->allow_modal === 'on' ? 1 : 0,
                'seo_description' => $request->seo_description,
                'fonts' => $request->fonts,
                'seo_tags' => $request->seo_tags,
                'copyright' => $request->copyright
            ]);
        } elseif ($request->type == 'pref') {
            
            // Ensure Configuration record exists first
            $config = Configuration::first();
            if (!$config) {
                $config = Configuration::create(['id' => 1]);
            }
            
            // Bootstrap toggle checkboxes: when checked, the field is present in request; when unchecked, it's absent
            // Standard HTML checkbox behavior: checked = name sent with value "on", unchecked = name not sent
            $dataArray = [
                'preloader_status' => $request->has('preloader_status') ? 1 : 0,
                'reg_enabled' => $request->has('user_reg') ? 1 : 0,
                'is_email_verification_on' => $request->has('is_email_verification_on') ? 1 : 0,
                'is_sms_verification_on' => $request->has('is_sms_verification_on') ? 1 : 0,
                'is_allow_kyc' => $request->has('user_kyc') ? 1 : 0,
                'enable_new_styles' => $request->has('enable_new_styles') ? 1 : 0,
            ];
            
            
            try {
                $config->update($dataArray);
                
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            $general = Configuration::first();
            
            if (!$general) {
                return ['type' => 'error', 'message' => 'Configuration not found. Please create a configuration first.'];
            }

            $general->kyc = array_values($request->kyc);

            $general->save();
        }
        
        // Clear configuration cache after update
        \Illuminate\Support\Facades\Cache::forget('app_configuration');
        
        return ['type' => 'success', 'message' => 'General setting has been updated.'];
    }
}
