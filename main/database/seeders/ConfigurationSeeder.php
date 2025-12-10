<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Configuration::updateOrCreate(
            ['id' => 1],
            [
                'appname' => 'AlgoExpertHub',
                'theme' => 'default',
                'currency' => 'usd',
                'pagination' => 10,
                'number_format' => 2,
                'alert' => 'izi',
                'logo' => 'logo.png',
                'favicon' => 'favicon.png',
                'reg_enabled' => true,
                'fonts' => [
                    'heading_font_url' => 'https://fonts.googleapis.com/css2?family=Roboto&display=swap',
                    'heading_font_family' => "'Roboto','sans-serif'",
                    'paragraph_font_url' => 'https://fonts.googleapis.com/css2?family=Roboto&display=swap',
                    'paragraph_font_family' => "'Roboto','sans-serif'"
                ],

                'is_email_verification_on' => false,
                'is_sms_verification_on' => false,
                'preloader_status' => false,
                'analytics_status' => false,
                'allow_modal' => true,
                'button_text' => "Accept All",
                'cookie_text' => "We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. By clicking 'Accept All', you consent to our use of cookies. You can decline non-essential cookies if you prefer.",
                'allow_recaptcha' => false,            

                'tdio_allow' => false,

                'seo_description' => "AlgoExpertHub - Your premier destination for algorithmic trading signals across Forex, Crypto, and Stock markets. Access AI-powered signals, automated trading, and institutional-grade strategies.",
                
                'seo_tags' => ['trading', 'signals', 'forex', 'crypto', 'stocks']
            ]
        );
    }
}
