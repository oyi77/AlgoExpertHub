<?php

namespace App\Helpers\Helper;

use App\Mail\BulkMail;
use App\Mail\TemplateMail;
use App\Models\Admin;
use App\Models\Configuration;
use App\Models\Content;
use App\Models\FrontendMedia;
use App\Models\Language;
use App\Models\Page;
use App\Models\PlanSubscription;
use App\Models\Referral;
use App\Models\ReferralCommission;
use App\Models\Template;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use App\Notifications\DepositNotification;
use App\Notifications\PlanSubscriptionNotification;
use App\Utility\Config;
use Image;
use DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Throwable;

class Helper
{

    const APP_VERSION = '5.0';

    public static function isInstalled()
    {
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        return false;
    }


    public static function languageSelection($code)
    {
        $default = Language::where('status', 0)->first()->code;

        if (session()->has('locale')) {
            if (session('locale') == $code) {
                return 'selected';
            }
        } else {
            if ($code == $default) {
                return 'selected';
            }
        }
    }

    public static function config()
    {
        return \App\Repositories\ConfigurationRepository::get();
    }

    /**
     * Convert object to array recursively, handling all edge cases
     * 
     * @param mixed $data Data to convert
     * @return array|mixed Converted array or original value
     */
    private static function objectToArray($data)
    {
        if (is_object($data)) {
            // Handle stdClass and other objects
            $data = get_object_vars($data);
        }
        
        if (is_array($data)) {
            // Recursively convert nested objects
            return array_map([self::class, 'objectToArray'], $data);
        }
        
        return $data;
    }

    /**
     * Safely convert value to string, handling objects and other types
     * 
     * @param mixed $value Value to convert
     * @return string String representation
     */
    private static function toString($value)
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_null($value)) {
            return '';
        }
        
        if (is_object($value)) {
            // Try to get string representation
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            // If object has a 'key' or 'name' property, use that
            if (isset($value->key)) {
                return self::toString($value->key);
            }
            if (isset($value->name)) {
                return self::toString($value->name);
            }
            // Last resort: return class name
            return get_class($value);
        }
        
        if (is_array($value)) {
            // For arrays, return first element if available
            return !empty($value) ? self::toString(reset($value)) : '';
        }
        
        // Fallback: try casting
        try {
            return (string) $value;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Translate a string using Laravel's translation system
     * 
     * @param string|mixed $key The translation key or default text
     * @param array|object $replace Optional replacement values (array or object)
     * @param string|null $locale Optional locale
     * @return string Translated string
     */
    public static function trans($key, $replace = [], $locale = null)
    {
        // Safely convert $key to string, handling objects and other types
        $key = self::toString($key);
        
        // If key is empty after conversion, return empty string
        if (empty($key)) {
            return '';
        }
        
        // Convert object to array for PHP 8.4 compatibility
        // This is critical for PHP 8.4 which has stricter type checking
        if (is_object($replace)) {
            // Use our custom conversion method that handles all edge cases
            $replace = self::objectToArray($replace);
        }
        
        // Ensure $replace is always an array
        if (!is_array($replace)) {
            $replace = [];
        }
        
        // Clean up $replace array - ensure all values are strings or primitives
        $replace = array_map(function($value) {
            if (is_object($value)) {
                return self::toString($value);
            }
            if (is_array($value)) {
                return self::objectToArray($value);
            }
            return $value;
        }, $replace);
        
        // Use the global trans() helper which handles PHP 8.4 better
        // The trans() helper internally uses Lang::get() with proper type handling
        try {
            if ($locale !== null) {
                return \trans($key, $replace, $locale);
            }
            return \trans($key, $replace);
        } catch (\TypeError $e) {
            // If trans() fails, try Lang facade directly
            try {
                if ($locale !== null) {
                    return Lang::get($key, $replace, $locale);
                }
                return Lang::get($key, $replace);
            } catch (\TypeError $e2) {
                // Last resort: return the key itself
                Log::warning('Translation type error in Helper::trans', [
                    'key' => $key,
                    'key_type' => gettype($key),
                    'replace_type' => gettype($replace),
                    'error' => $e2->getMessage()
                ]);
                return $key;
            }
        } catch (\Exception $e) {
            // Fallback for any other translation errors
            Log::warning('Translation error in Helper::trans', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $key;
        }
    }

    public static function imagePath($folder, $default = false)
    {
        $general = Helper::config();

        if ($default) {
            return 'asset/images/' . $folder;
        }

        $theme = $general && $general->theme ? $general->theme : 'default';
        return 'asset/frontend/' . $theme . '/images/' . $folder;
    }

    public static function fetchImage($folder, $filename, $default = false)
    {
        $general = Helper::config();
        if ($default == true) {
            if (file_exists(Helper::imagePath($folder, $default) . '/' . $filename) && $filename != null) {
                return asset('asset/images/' . $folder . '/' . $filename);
            }
            return asset('asset/images/placeholder.png');
        }
        if (file_exists(Helper::imagePath($folder) . '/' . $filename) && $filename != null) {
            $theme = $general && $general->theme ? $general->theme : 'default';
            return asset('asset/frontend/' . $theme . '/images/' . $folder . '/' . $filename);
        }
        return asset('asset/images/placeholder.png');
    }

    public static function cssLib($folder, $filename)
    {
        try {
            $config = self::config();
            $template = $config && $config->theme ? $config->theme : 'default';
        } catch (\Exception $e) {
            $template = 'default';
        }

        if ($folder == 'backend') {
            return asset("asset/{$folder}/css/{$filename}");
        }

        // Check if file exists in current theme
        $assetPath = public_path("asset/{$folder}/{$template}/css/{$filename}");
        if (file_exists($assetPath)) {
            return asset("asset/{$folder}/{$template}/css/{$filename}");
        }

        // Use inheritance chain to find asset in parent themes
        try {
            $themeManager = app(\App\Services\ThemeManager::class);
            $inheritanceChain = $themeManager->getThemeInheritanceChain($template);
            
            // Skip first (current theme) as we already checked it
            foreach (array_slice($inheritanceChain, 1) as $parentTheme) {
                $parentAssetPath = public_path("asset/{$folder}/{$parentTheme}/css/{$filename}");
                if (file_exists($parentAssetPath)) {
                    return asset("asset/{$folder}/{$parentTheme}/css/{$filename}");
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }

        // Final fallback to default theme
        return asset("asset/{$folder}/default/css/{$filename}");
    }

    public static function jsLib($folder, $filename)
    {
        try {
            $config = self::config();
            $template = $config && $config->theme ? $config->theme : 'default';
        } catch (\Exception $e) {
            $template = 'default';
        }

        if ($folder == 'backend') {
            return asset("asset/{$folder}/js/{$filename}");
        }

        // Check if file exists in current theme
        $assetPath = public_path("asset/{$folder}/{$template}/js/{$filename}");
        if (file_exists($assetPath)) {
            return asset("asset/{$folder}/{$template}/js/{$filename}");
        }

        // Use inheritance chain to find asset in parent themes
        try {
            $themeManager = app(\App\Services\ThemeManager::class);
            $inheritanceChain = $themeManager->getThemeInheritanceChain($template);
            
            // Skip first (current theme) as we already checked it
            foreach (array_slice($inheritanceChain, 1) as $parentTheme) {
                $parentAssetPath = public_path("asset/{$folder}/{$parentTheme}/js/{$filename}");
                if (file_exists($parentAssetPath)) {
                    return asset("asset/{$folder}/{$parentTheme}/js/{$filename}");
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }

        // Final fallback to default theme
        return asset("asset/{$folder}/default/js/{$filename}");
    }

    public static function verificationCode($length)
    {
        if ($length == 0) {
            return 0;
        }

        $min = pow(10, $length - 1);
        $max = 0;
        while ($length > 0 && $length--) {
            $max = ($max * 10) + 9;
        }
        return random_int($min, $max);
    }

    public static function fireMail($data, $template)
    {
        $html = $template->template;

        $general = self::config();



        foreach ($data as $key => $value) {
            $html = str_replace("%" . $key . "%", $value, $html);
        }

        $emailMethod = optional($general)->email_method ?? 'smtp';
        if ($emailMethod == 'php') {
            $appname = optional($general)->appname ?? 'App';
            $emailFrom = optional($general)->email_sent_from ?? 'noreply@example.com';
            $headers = "From: $appname <$emailFrom> \r\n";
            $headers .= "Reply-To: $appname <$emailFrom> \r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            @mail($data['email'], $template->subject, $html, $headers);
        } else {
            try {

                Mail::to($data['email'])->send(
                    new TemplateMail($template->subject, $html)
                );
            } catch (Throwable $exception) {

                return ['type' => 'invalid', 'message' => 'Invalid Email Configuration'];
            }
        }
    }

    public static function commonMail($data)
    {


        $general = self::config();

        if (!isset($data['email'])) {
            $data['email'] = optional($general)->email_sent_from ?? 'noreply@example.com';
        }

        $emailMethod = optional($general)->email_method ?? 'smtp';
        if ($emailMethod == 'php') {
            $appname = optional($general)->appname ?? 'App';
            $emailFrom = optional($general)->email_sent_from ?? 'noreply@example.com';
            $headers = "From: $appname <$emailFrom> \r\n";
            $headers .= "Reply-To: $appname <$emailFrom> \r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=utf-8\r\n";
            @mail($data['email'], $data['subject'], $data['message'], $headers);
        } else {
            try {

                Mail::to($data['email'])->send(
                    new BulkMail($data['subject'], $data['message'])
                );
            } catch (Throwable $exception) {
                Log::error($exception);

                return ['type' => 'error', 'message' => 'Invalid Email Configuration'];
            }
        }
    }



    public static function pagination()
    {
        return optional(self::config())->pagination ?? 10;
    }

    public static function formatter($number)
    {
        $config = optional(self::config())->decimal_precision ?? 2;
        $currency = optional(self::config())->currency ?? 'USD';

        return number_format($number, $config) . ' ' . $currency;
    }


    public static function formatOnlyNumber($number)
    {
        $config = optional(self::config())->decimal_precision ?? 2;

        return number_format($number, $config);
    }

    /**
     * Format signal price based on pair type and market
     * 
     * @param float $price The price to format
     * @param string|null $pairName The currency pair name (e.g., "EUR/USD", "BTC/USDT", "XAU/USD")
     * @param string|null $marketName The market name (e.g., "Forex", "Crypto", "Commodities")
     * @return string Formatted price with appropriate decimals
     */
    public static function formatSignalPrice($price, $pairName = null, $marketName = null)
    {
        if ($price === null || $price === '') {
            return 'N/A';
        }

        $price = (float) $price;
        $decimals = self::getSignalPriceDecimals($pairName, $marketName);

        // Format with appropriate decimals and remove trailing zeros for cleaner display
        $formatted = number_format($price, $decimals, '.', '');
        
        // Remove trailing zeros but keep at least one decimal place for small numbers
        if (strpos($formatted, '.') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
            // If we removed all decimals, add back one if the original had decimals
            if (strpos($formatted, '.') === false && $decimals > 0) {
                $formatted = number_format($price, min($decimals, 2), '.', '');
            }
        }

        return $formatted;
    }

    /**
     * Format signal outcome status with badge
     * 
     * @param string|null $outcome The outcome status
     * @return string HTML badge for outcome status
     */
    public static function formatSignalOutcome($outcome = null)
    {
        if (empty($outcome)) {
            return '<span class="badge bg-secondary">' . __('Open') . '</span>';
        }

        $badges = [
            'tp_hit' => ['class' => 'bg-success', 'text' => __('TP Hit')],
            'sl_hit' => ['class' => 'bg-danger', 'text' => __('SL Hit')],
            'manual_close' => ['class' => 'bg-info', 'text' => __('Manual Close')],
            'cancelled' => ['class' => 'bg-warning', 'text' => __('Cancelled')],
            'open' => ['class' => 'bg-primary', 'text' => __('Open')],
            'expired' => ['class' => 'bg-secondary', 'text' => __('Expired')],
        ];

        $badge = $badges[$outcome] ?? ['class' => 'bg-secondary', 'text' => ucfirst($outcome)];

        return '<span class="badge ' . $badge['class'] . '">' . $badge['text'] . '</span>';
    }

    /**
     * Format provider name with proper capitalization
     * 
     * @param string|null $provider The provider name (e.g., 'metaapi', 'binance', 'coinbase')
     * @return string Formatted provider name (e.g., 'MetaApi', 'Binance', 'Coinbase')
     */
    public static function formatProviderName($provider = null)
    {
        if (empty($provider)) {
            return 'N/A';
        }

        // Known provider name mappings
        $providerMap = [
            'metaapi' => 'MetaApi',
            'metaapi.io' => 'MetaApi',
            'binance' => 'Binance',
            'coinbase' => 'Coinbase',
            'kraken' => 'Kraken',
            'bitfinex' => 'Bitfinex',
            'okx' => 'OKX',
            'bybit' => 'Bybit',
            'huobi' => 'Huobi',
            'gate.io' => 'Gate.io',
            'kucoin' => 'KuCoin',
            'ftx' => 'FTX',
        ];

        $lowerProvider = strtolower(trim($provider));
        
        // Check if we have a mapping
        if (isset($providerMap[$lowerProvider])) {
            return $providerMap[$lowerProvider];
        }

        // Default: Capitalize first letter of each word
        return ucwords(str_replace(['_', '-'], ' ', $provider));
    }

    /**
     * Get appropriate decimal places for signal price based on pair and market
     * 
     * @param string|null $pairName The currency pair name
     * @param string|null $marketName The market name
     * @return int Number of decimal places (2-6)
     */
    public static function getSignalPriceDecimals($pairName = null, $marketName = null)
    {
        $pairName = $pairName ?? '';
        $marketName = $marketName ?? '';
        
        // Forex pairs: 5 decimals (standard for major pairs)
        if (stripos($marketName, 'forex') !== false || 
            (stripos($pairName, '/') !== false && 
             (stripos($pairName, 'USD') !== false || 
              stripos($pairName, 'EUR') !== false || 
              stripos($pairName, 'GBP') !== false || 
              stripos($pairName, 'JPY') !== false || 
              stripos($pairName, 'CHF') !== false || 
              stripos($pairName, 'AUD') !== false || 
              stripos($pairName, 'CAD') !== false || 
              stripos($pairName, 'NZD') !== false))) {
            return 5;
        }

        // Gold/XAU: 2 decimals
        if (stripos($pairName, 'XAU') !== false || 
            stripos($pairName, 'GOLD') !== false || 
            stripos($marketName, 'commodit') !== false) {
            return 2;
        }

        // Crypto major pairs (BTC, ETH): 2 decimals
        if (stripos($pairName, 'BTC') !== false || 
            stripos($pairName, 'ETH') !== false) {
            // For BTC/USDT, ETH/USDT - use 2 decimals
            if (stripos($pairName, 'USDT') !== false || stripos($pairName, 'USD') !== false) {
                return 2;
            }
            // For BTC pairs with other cryptos - use 4 decimals
            return 4;
        }

        // Crypto altcoins: 4-6 decimals based on price range
        if (stripos($marketName, 'crypto') !== false || 
            stripos($pairName, 'USDT') !== false || 
            stripos($pairName, 'USDC') !== false) {
            // If price is very small (< 0.01), use 6 decimals
            // If price is small (< 1), use 4 decimals
            // Otherwise use 2 decimals
            // But we can't check price here, so default to 4 for crypto
            return 4;
        }

        // Stocks/Indices: 2 decimals
        if (stripos($marketName, 'stock') !== false || 
            stripos($marketName, 'index') !== false) {
            return 2;
        }

        // Default: 2 decimals
        return 2;
    }

    public static function languages()
    {
        return Language::latest()->get();
    }

    public static function pages()
    {
        return Page::where('status', 1)->where('name', '!=', 'home')->get();
    }

    public static function notifications()
    {
        return auth()->guard('admin')->user()->unreadNotifications()->latest()->get();
    }

    public static function sidebarData()
    {
        $data['deactiveUser'] = User::where('status', 0)->count();
        $data['emailUnverified'] = User::where('is_email_verified', 0)->count();
        $data['smsUnverified'] = User::where('is_sms_verified', 0)->count();
        $data['kycUnverified'] = User::whereIn('is_kyc_verified', [0, 2])->count();
        $data['kyc_req'] = User::where('is_kyc_verified', 2)->where('kyc_information', '!=', null)->count();

        $data['pendingTicket'] = Ticket::where('status', 2)->count();

        $data['pendingWithdraw'] = Withdraw::where('status', 0)->count();

        return $data;
    }

    public static function theme()
    {
        try {
            $config = Configuration::first();
            if ($config && $config->theme) {
                return 'frontend.' . $config->theme . '.';
            }
            // Fallback to default if theme not found
            return 'frontend.default.';
        } catch (\Exception $e) {
            // Fallback to default on error
            return 'frontend.default.';
        }
    }

    public static function themeView($view)
    {
        try {
            $config = Configuration::first();
            $theme = $config && $config->theme ? $config->theme : 'default';
            
            // If default theme, use default directly
            if ($theme === 'default') {
                return 'frontend.default.' . $view;
            }
            
            // Check if theme-specific view exists
            $themeView = 'frontend.' . $theme . '.' . $view;
            if (view()->exists($themeView)) {
                return $themeView;
            }
            
            // Use inheritance chain to find view in parent themes
            $themeManager = app(\App\Services\ThemeManager::class);
            $inheritanceChain = $themeManager->getThemeInheritanceChain($theme);
            
            // Skip first (current theme) as we already checked it
            foreach (array_slice($inheritanceChain, 1) as $parentTheme) {
                $parentView = 'frontend.' . $parentTheme . '.' . $view;
                if (view()->exists($parentView)) {
                    return $parentView;
                }
            }
            
            // Final fallback to default theme
            return 'frontend.default.' . $view;
        } catch (\Exception $e) {
            // Fallback to default on error
            return 'frontend.default.' . $view;
        }
    }

    public static function landingView($view = 'index')
    {
        try {
            $config = Configuration::first();
            $landing = $config && $config->landing_page ? $config->landing_page : null;

            if ($landing) {
                $landingView = "frontend.landings.{$landing}.{$view}";
                if (view()->exists($landingView)) {
                    return $landingView;
                }
            }

            // Fallback to theme home view
            return self::themeView('home');
        } catch (\Exception $e) {
            return self::themeView('home');
        }
    }

    public static function backendTheme()
    {
        try {
            $config = Configuration::first();
            if ($config && $config->backend_theme && $config->backend_theme !== 'default') {
                return 'backend.' . $config->backend_theme . '.';
            }
            // Fallback to default (no prefix for default backend theme)
            return 'backend.';
        } catch (\Exception $e) {
            // Fallback to default on error
            return 'backend.';
        }
    }


    public static function makeDir($path)
    {
        if (file_exists($path)) return true;
        return mkdir($path, 0775, true);
    }

    public static function removeFile($path)
    {
        return file_exists($path) && is_file($path) ? unlink($path) : false;
    }



    public static function frontendFormatter($key)
    {
        return ucwords(str_replace('_', ' ', $key));
    }


    public static function filePath($folder_name, $default = false)
    {
        $general = self::config();

        if ($default) {
            return 'asset/images/' . $folder_name;
        }

        $theme = $general && $general->theme ? $general->theme : 'default';
        return 'asset/frontend/' . $theme . '/images/' . $folder_name;
    }


    public static function saveImage($image, $directory, $removeFile = '')
    {
        $path = self::makeDir($directory);

        if (!empty($removeFile)) {
            self::removeFile($directory . '/' . $removeFile);
        }

        $filename = uniqid() . time() . '.' . $image->getClientOriginalExtension();

        if ($image->getClientOriginalExtension() == 'gif') {
            copy($image->getRealPath(), $directory . '/' . $filename);
        } else {
            $image = Image::make($image);
            $image->save($directory . '/' . $filename);
        }

        return $filename;
    }


    public static function getFile($folder_name, $filename, $default = false)
    {
        // Ensure $filename is a string, not an object
        if (is_object($filename)) {
            // Try to get string representation safely
            if (method_exists($filename, '__toString')) {
                try {
                    $filename = (string) $filename;
                } catch (\Exception $e) {
                    return asset('asset/images/placeholder.png');
                }
            } else {
                // Object can't be converted to string
                return asset('asset/images/placeholder.png');
            }
        }
        
        // Convert to string if it's not null, using safe conversion
        if ($filename !== null) {
            try {
                $filename = (string) $filename;
            } catch (\Exception $e) {
                return asset('asset/images/placeholder.png');
            }
        }

        $general = self::config();

        if ($default) {
            if (file_exists(self::filePath($folder_name, $default) . '/' . $filename) && $filename != null) {

                return asset('asset/images/' . $folder_name . '/' . $filename);
            }
        }

        $theme = $general && $general->theme ? $general->theme : 'default';

        // Check if file exists in current theme
        $filePath = self::filePath($folder_name) . '/' . $filename;
        if (file_exists($filePath) && $filename != null) {
            return asset('asset/frontend/' . $theme . '/images/' . $folder_name . '/' . $filename);
        }

        // Use inheritance chain to find file in parent themes
        try {
            $themeManager = app(\App\Services\ThemeManager::class);
            $inheritanceChain = $themeManager->getThemeInheritanceChain($theme);
            
            // Skip first (current theme) as we already checked it
            foreach (array_slice($inheritanceChain, 1) as $parentTheme) {
                $parentFilePath = public_path('asset/frontend/' . $parentTheme . '/images/' . $folder_name . '/' . $filename);
                if (file_exists($parentFilePath) && $filename != null) {
                    return asset('asset/frontend/' . $parentTheme . '/images/' . $folder_name . '/' . $filename);
                }
            }
        } catch (\Exception $e) {
            // Fall through to placeholder
        }

        return asset('asset/images/placeholder.png');
    }

    public static function sectionConfig()
    {
        return Config::sectionsSelectable();
    }

    public static function activeMenu($route)
    {
        if (is_array($route)) {
            if (in_array(url()->current(), $route)) {
                return 'active';
            }
        }
        if ($route == url()->current()) {
            return 'active';
        }
    }

    public static function builder($section, $collection = false)
    {

        $theme = optional(self::config())->theme ?? 'default';
        if ($collection) {
            return Content::where('type', 'iteratable')->where('theme', $theme)->where('name', $section)->get();
        }

        return Content::where('type', 'non_iteratable')->where('theme', $theme)->where('name', $section)->first();
    }

    public static function media($section, $key,  $type = false, $id = null)
    {
        if ($type) {
            $media = FrontendMedia::where('content_id', $id)->where('section_name', $section)->where('type', 'iteratable')->first();

            if ($media) {
                return self::getFile($section, optional($media->media)->$key);
            } else {
                return self::getFile($section, '');
            }
        }


        $media = FrontendMedia::where('section_name', $section)->where('type', 'non_iteratable')->first();


        return self::getFile($section, optional($media->media)->$key);
    }

    public static function colorText($haystack, $needle)
    {
        $replace = "<span>{$needle}</span>";

        return str_replace($needle, $replace, $haystack);
    }


    public static function setEnv(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {

                $str .= "\n";
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) return false;
        return true;
    }


    public static function singleMenu($routeName)
    {
        $class = 'active';

        if (request()->routeIs($routeName)) {
            return $class;
        }
        return '';
    }



    public static function paymentSuccess($deposit, $fee_amount, $transaction)
    {
        $general = Configuration::first();

        $admin = Admin::where('type', 'super')->first();

        $user = auth()->user();

        if (session('type') == 'deposit') {
            $user->balance = $user->balance + $deposit->amount;

            $user->save();

            $admin->notify(new DepositNotification($deposit, 'online', 'deposit'));
        }

        $deposit->status = 1;

        $deposit->save();

        $data = [
            'plan_id' => $deposit->plan_id,
            'user_id' => $user->id,
        ];


        if (!(session('type') == 'deposit')) {

            $subscription = self::subscription($data, $deposit);
            $admin->notify(new PlanSubscriptionNotification($subscription));
            self::referMoney(auth()->id(), $deposit->user->refferedBy, 'invest', $deposit->amount);
        }

        Transaction::create([
            'trx' => $deposit->trx,
            'amount' => $deposit->amount,
            'details' => 'Payment Successfull',
            'charge' => $fee_amount,
            'type' => '+',
            'user_id' => auth()->id()
        ]);

        $template = Template::where('name', 'payment_successfull')->where('status', 1)->first();

        if ($template) {

            self::fireMail([
                'username' => $deposit->user->username,
                'app_name' => $general->appname,
                'email' => $deposit->user->email,
                'plan' => $deposit->plan->name ?? 'Deposit',
                'trx' => $transaction,
                'amount' => $deposit->amount,
                'currency' => $general->currency,
            ], $template);
        }
    }

    private static function subscription($data, $deposit)
    {
        $subscription = auth()->user()->subscriptions;

        if ($subscription) {
            DB::table('plan_subscriptions')->where('user_id', auth()->id())->update(['is_current' => 0]);
        }

        $id = PlanSubscription::create([
            'plan_id' => $data['plan_id'],
            'user_id' => $data['user_id'],
            'is_current' => 1,
            'plan_expired_at' => $deposit->plan_expired_at
        ]);

        return $id;
    }


    public static function referMoney($from, $to, $refferal_type, $amount)
    {
        $general = Configuration::first();
        if ($general->is_referral_enabled == 0) {
            return;
        }

        $referral = Referral::where('type', $refferal_type)->where('status', 1)->first();

        if ($referral) {
            $commissions = ReferralCommission::where('referral_id', $referral->id)->get();
            $user = User::find($to);
            $i = 1;
            foreach ($commissions as $commission) {
                if ($user) {
                    $commission_amount = ($amount * $commission->commission) / 100;
                    $user->balance = $user->balance + $commission_amount;
                    $user->save();

                    Transaction::create([
                        'trx' => Str::random(12),
                        'user_id' => $user->id,
                        'amount' => $commission_amount,
                        'charge' => 0,
                        'type' => '+',
                        'details' => 'Referral Commission from ' . auth()->user()->username . ' for level ' . $i
                    ]);

                    $user = $user->refferedBy;
                    $i++;
                }
            }
        }
    }

    /**
     * Generate navbar menu HTML from pages
     * 
     * @return string HTML for navbar menu items
     */
    public static function navbarMenus()
    {
        try {
            $html = '';
            
            // Home link
            $homeActive = request()->routeIs('home') ? 'active' : '';
            $html .= '<li class="nav-item"><a class="nav-link ' . $homeActive . '" href="' . route('home') . '">' . __('Home') . '</a></li>';
            
            // Get active pages from database
            $pages = Page::where('status', 1)
                ->where('name', '!=', 'home')
                ->orderBy('id')
                ->get();
            
            foreach ($pages as $page) {
                $isActive = request()->is('pages/' . $page->slug) ? 'active' : '';
                $pageName = __(ucfirst(str_replace(['-', '_'], ' ', $page->name)));
                $html .= '<li class="nav-item"><a class="nav-link ' . $isActive . '" href="' . route('pages', $page->slug) . '">' . $pageName . '</a></li>';
            }
            
            return $html;
        } catch (\Exception $e) {
            // Fallback to basic menu if error occurs
            $homeActive = request()->routeIs('home') ? 'active' : '';
            return '<li class="nav-item"><a class="nav-link ' . $homeActive . '" href="' . route('home') . '">' . __('Home') . '</a></li>';
        }
    }
}
