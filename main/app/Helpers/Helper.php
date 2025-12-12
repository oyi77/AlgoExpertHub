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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Throwable;
class Helper
{

    const APP_VERSION = '5.0';

    public static function isInstalled()
    {
        if (!file_exists(storage_path('installed'))) {
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

        return asset("asset/{$folder}/{$template}/css/{$filename}");
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

        return asset("asset/{$folder}/{$template}/js/{$filename}");
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

        $general = self::config();

        if ($default) {
            if (file_exists(self::filePath($folder_name, $default) . '/' . $filename) && $filename != null) {

                return asset('asset/images/' . $folder_name . '/' . $filename);
            }
        }

        if (file_exists(self::filePath($folder_name) . '/' . $filename) && $filename != null) {
            $theme = $general && $general->theme ? $general->theme : 'default';
            return asset('asset/frontend/' . $theme . '/images/' . $folder_name . '/' . $filename);
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

        $user_id = $from;

        $level = Referral::where('status', 1)->where('type', $refferal_type)->first();

        $counter = $level ? count($level->level) : 0;

        $general = Configuration::first();

        for ($i = 0; $i < $counter; $i++) {

            if ($to) {

                if ($refferal_type == 'interest') {
                    $commission = $level->commission[$i];
                } else {
                    $commission = ($level->commission[$i] * $amount) / 100;
                }


                $to->balance = $to->balance + $commission;

                $to->save();

                Transaction::create([
                    'trx' => Str::upper(Str::random(16)),
                    'user_id' => $to->id,
                    'amount' => $commission,
                    'charge' => 0,
                    'details' => 'Refferal Commission from level ' . ($i + 1) . ' user',
                    'type' => '+'
                ]);

                ReferralCommission::create([
                    'commission_to' => $to->id,
                    'commission_from' => $user_id,
                    'amount' => $commission,
                    'purpouse' => $refferal_type === 'invest' ? 'Return invest commission' : 'Return Interest Commission'

                ]);


                $template = Template::where('name', 'refer_commission')->where('status', 1)->first();




                if ($template) {
                    self::fireMail([
                        'username' => $to->username,
                        'email' => $to->email,
                        'app_name' => $general->appname,
                        'refer_user' => User::find($from)->username,
                        'amount' => $commission,
                        'currency' => $general->currency,
                    ], $template);
                }

                $from = $to->id;
                $to = $to->refferedBy;
            }
        }
    }

    public static function navbarMenus()
    {
        $dropdowns = Page::where('name', '!=', 'home')->where('is_dropdown', true)->where('status', 1)->orderBy('order', 'ASC')->get();

        $nonDropdowns = Page::where('name', '!=', 'home')->where('is_dropdown', false)->where('status', 1)->orderBy('order', 'ASC')->get();

        $home = route('home');

        $dropdownsBuilder = '';

        $homeText = __('Home');
        $homeText = ucfirst(strtolower(trim($homeText))); // Ensure first letter is capitalized
        $nonDropdownsBuilder = "<li class='nav-item'>
        <a class='nav-link' href='" . $home . "'>" . $homeText . "</a>
    </li>";
        $html = '';


        foreach ($nonDropdowns as $page) {
            $route = route('pages', $page->slug);
            $nonDropdownsBuilder .= "
                <li class='nav-item'>
                                <a class='nav-link' href='" . $route . "'>" . __($page->name) . "</a>
                            </li>
                ";
        }

        if ($nonDropdowns->count() > 0) {
            $html .= $nonDropdownsBuilder;
        }



        foreach ($dropdowns as $drop) {
            $route = route('pages', $drop->slug);
            $dropdownsBuilder .= "<li><a class='dropdown-item' href='" . $route . "'>" . __($drop->name) . "</a></li>";
        }

        if ($dropdowns->count() > 0) {
            $html .= " <li class='nav-item dropdown'>
                        <a class='nav-link dropdown-toggle' href='#' id='navbarDropdown' role='button'
                            data-bs-toggle='dropdown' aria-expanded='false'>
                            " . __('Pages') . "
                        </a>
                        <ul class='dropdown-menu' aria-labelledby='navbarDropdown'>
                            " . $dropdownsBuilder . "
                        </ul>
                </li>";
        }



        return $html;
    }


    public static function trans($key)
    {
        $jsonFile = session('locale') ?? 'en';

        $jsonArray = json_decode(file_get_contents(resource_path('lang/sections/' . $jsonFile . '.json')), true) ?? [];


        $key = preg_replace('/\s+/S', " ", $key);

        $key = ucfirst(strtolower(trim($key)));

        if (!array_key_exists($key, $jsonArray)) {

            $jsonArray[$key] = $key;

            file_put_contents(resource_path('lang/sections/' . $jsonFile . '.json'), json_encode($jsonArray));
        }

        return $jsonArray[$key];
    }

    /**
     * Command Execution Helper
     * Detects Docker vs binary environment and provides unified command execution
     */
    
    /**
     * Check if we're running in Docker container
     * 
     * @return bool
     */
    public static function isDockerEnvironment(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        
        $cached = file_exists('/.dockerenv') || getenv('DOCKER_CONTAINER') || getenv('DOCKER_HOST');
        return $cached;
    }

    /**
     * Get command execution mode (docker or binary)
     * 
     * @return string 'docker' or 'binary'
     */
    public static function getCommandMode(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (self::isDockerEnvironment()) {
            $cached = 'binary'; // Inside Docker, use binaries directly
        } else {
            // Outside Docker, check if we can use docker exec
            $dockerAvailable = self::checkDockerAvailable();
            $cached = $dockerAvailable ? 'docker' : 'binary';
        }
        
        return $cached;
    }

    /**
     * Check if Docker is available and accessible
     * 
     * @return bool
     */
    protected static function checkDockerAvailable(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (!function_exists('exec')) {
            $cached = false;
            return false;
        }

        exec('which docker 2>&1', $output, $return);
        if ($return !== 0) {
            $cached = false;
            return false;
        }

        // Test docker command
        exec('docker ps > /dev/null 2>&1', $testOutput, $testReturn);
        $cached = $testReturn === 0;
        return $cached;
    }

    /**
     * Get PHP container name from environment or detect
     * 
     * @return string|null
     */
    public static function getPhpContainer(): ?string
    {
        static $cached = null;
        // Clear cache if we're in a new request (for testing)
        // In production, this will cache properly
        if ($cached !== null && !defined('TESTING_CONTAINER_DETECTION')) {
            return $cached;
        }

        // Check environment variable first
        $container = getenv('PHP_DOCKER_CONTAINER');
        if ($container) {
            $cached = $container;
            return $container;
        }

        // If we're running inside a container, try to detect our container name
        // Since we can't access docker from inside, we'll try common patterns or env vars
        if (file_exists('/.dockerenv') || file_exists('/proc/self/cgroup')) {
            // We're inside a Docker container
            // Try common 1Panel PHP container name patterns (since we know the pattern)
            $possibleNames = [
                '1Panel-php8-mrTy',
                '1panel-php8-mrTy',
                '1Panel-php8',
                '1panel-php8',
            ];
            
            // Try to verify by checking if we can exec into ourselves (won't work, but try anyway)
            // Actually, since we're IN the container, we can't verify via docker exec
            // So we'll just return the first pattern that matches common 1Panel naming
            // Or check HOSTNAME env var which might be set
            $hostnameEnv = getenv('HOSTNAME');
            if ($hostnameEnv && preg_match('/^(1Panel|1panel|php)/i', $hostnameEnv)) {
                $cached = $hostnameEnv;
                \Log::info('Detected PHP container from HOSTNAME env', ['container' => $hostnameEnv]);
                return $hostnameEnv;
            }
            
            // For 1Panel, try the most common pattern first
            // We'll try to use it, and the backup code will verify mysqldump exists
            $assumedName = '1Panel-php8-mrTy'; // Most common 1Panel PHP container name
            $cached = $assumedName;
            \Log::info('Assuming PHP container name (running inside container, cannot access docker)', [
                'container' => $assumedName,
                'note' => 'Will be verified when mysqldump is checked'
            ]);
            return $assumedName;
        }

        if (!self::checkDockerAvailable()) {
            $cached = null;
            return null;
        }

        // First, try to find any container with "php" in the name (more reliable)
        exec('docker ps --format "{{.Names}}" | grep -i php 2>&1', $phpContainers, $return);
        \Log::info('PHP container detection - grep result', [
            'return_code' => $return,
            'containers' => $phpContainers,
            'docker_available' => self::checkDockerAvailable()
        ]);
        
        if ($return === 0 && !empty($phpContainers)) {
            foreach ($phpContainers as $containerName) {
                $containerName = trim($containerName);
                if (!empty($containerName) && stripos($containerName, 'phpmyadmin') === false) {
                    // Skip phpmyadmin, verify container has PHP
                    exec("docker exec {$containerName} php --version 2>&1", $verifyOutput, $verifyReturn);
                    \Log::info('PHP container verification', [
                        'container' => $containerName,
                        'verify_return' => $verifyReturn,
                        'output' => $verifyOutput
                    ]);
                    if ($verifyReturn === 0) {
                        $cached = $containerName;
                        \Log::info('PHP container detected', ['container' => $containerName]);
                        return $containerName;
                    }
                }
            }
        }

        // Fallback: Try common 1Panel PHP container patterns
        $possibleContainers = [
            '1Panel-php8-mrTy',
            '1panel-php8-mrTy',
            '1Panel-php',
            '1panel-php',
            'php',
            'php-fpm',
        ];

        foreach ($possibleContainers as $containerName) {
            // Try with exec (more reliable than shell_exec)
            $execOutput = [];
            $execReturn = 0;
            exec("docker ps --filter name={$containerName} --format '{{.Names}}' 2>&1", $execOutput, $execReturn);
            if ($execReturn === 0 && !empty($execOutput) && trim($execOutput[0]) === $containerName) {
                // Verify container has PHP
                exec("docker exec {$containerName} php --version 2>&1", $verifyOutput, $verifyReturn);
                if ($verifyReturn === 0) {
                    $cached = $containerName;
                    return $containerName;
                }
            }
        }

        $cached = null;
        return null;
    }

    /**
     * Get MySQL container name from environment or detect
     * 
     * @return string|null
     */
    public static function getMysqlContainer(): ?string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        // Check environment variable first
        $container = getenv('MYSQL_DOCKER_CONTAINER');
        if ($container) {
            $cached = $container;
            return $container;
        }

        if (!self::checkDockerAvailable()) {
            $cached = null;
            return null;
        }

        // Try to find MySQL container
        exec('docker ps --format "{{.Names}}" | grep -i mysql 2>&1', $containers, $return);
        if ($return === 0 && !empty($containers)) {
            $container = trim($containers[0]);
            if (!empty($container)) {
                // Verify container has mysqldump
                exec("docker exec {$container} mysqldump --version 2>&1", $verifyOutput, $verifyReturn);
                if ($verifyReturn === 0) {
                    $cached = $container;
                    return $container;
                }
            }
        }

        // Try common container names
        $possibleContainers = [
            '1Panel-mysql-L7KM',
            '1panel-mysql-L7KM',
            'mysql',
            '1panel-mysql',
            '1Panel-mysql',
        ];

        foreach ($possibleContainers as $containerName) {
            exec("docker exec {$containerName} mysqldump --version 2>&1", $testOutput, $testReturn);
            if ($testReturn === 0) {
                $cached = $containerName;
                return $containerName;
            }
        }

        $cached = null;
        return null;
    }

    /**
     * Get Redis container name from environment or detect
     * 
     * @return string|null
     */
    public static function getRedisContainer(): ?string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        // Check environment variable first
        $container = getenv('REDIS_DOCKER_CONTAINER');
        if ($container) {
            $cached = $container;
            return $container;
        }

        if (!self::checkDockerAvailable()) {
            $cached = null;
            return null;
        }

        // Try to find Redis container
        exec('docker ps --format "{{.Names}}" | grep -i redis 2>&1', $containers, $return);
        if ($return === 0 && !empty($containers)) {
            $container = trim($containers[0]);
            if (!empty($container)) {
                // Verify container has redis-cli
                exec("docker exec {$container} redis-cli --version 2>&1", $verifyOutput, $verifyReturn);
                if ($verifyReturn === 0) {
                    $cached = $container;
                    return $container;
                }
            }
        }

        // Try common container names
        $possibleContainers = ['redis', '1panel-redis', '1Panel-redis'];

        foreach ($possibleContainers as $containerName) {
            exec("docker exec {$containerName} redis-cli --version 2>&1", $testOutput, $testReturn);
            if ($testReturn === 0) {
                $cached = $containerName;
                return $containerName;
            }
        }

        $cached = null;
        return null;
    }

    /**
     * Map host path to container path
     * 
     * @param string $hostPath
     * @return string
     */
    public static function mapPathToContainer(string $hostPath): string
    {
        $pathMappings = [
            '/opt/1panel/apps/openresty/openresty' => '/www',
            '/opt/1panel/apps/openresty' => '/www',
        ];

        foreach ($pathMappings as $hostPrefix => $containerPrefix) {
            if (strpos($hostPath, $hostPrefix) === 0) {
                return str_replace($hostPrefix, $containerPrefix, $hostPath);
            }
        }

        // Fallback: try to extract path after /www
        if (preg_match('#(/www/sites/[^/]+/index/main)#', $hostPath, $matches)) {
            return $matches[1];
        }

        // Final fallback
        return '/www/sites/aitradepulse.com/index/main';
    }

    /**
     * Build PHP command (handles Docker)
     * 
     * @param string $command PHP command to execute (e.g., 'artisan queue:work')
     * @param string|null $workingDir Working directory (defaults to base_path())
     * @return array ['command' => string, 'path' => string]
     */
    public static function buildPhpCommand(string $command = '', ?string $workingDir = null): array
    {
        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $mode = self::getCommandMode();
        $workingDir = $workingDir ?? base_path();

        if ($mode === 'docker') {
            $container = self::getPhpContainer();
            if ($container) {
                $containerPath = self::mapPathToContainer($workingDir);
                // Verify path exists in container
                $pathCheck = shell_exec("docker exec {$container} test -d {$containerPath} && echo 'exists' 2>/dev/null");
                if ($pathCheck && trim($pathCheck) === 'exists') {
                    $fullCommand = $command ? "php {$command}" : 'php';
                    return [
                        'command' => "docker exec {$container} {$fullCommand}",
                        'path' => $containerPath
                    ];
                }
            }
        }

        // Fallback to binary
        $fullCommand = $command ? "{$phpBinary} {$command}" : $phpBinary;
        return [
            'command' => $fullCommand,
            'path' => $workingDir
        ];
    }

    /**
     * Build MySQL command (handles Docker)
     * 
     * @param string $command MySQL command (e.g., 'mysql', 'mysqldump')
     * @param array $args Command arguments
     * @return string Full command string
     */
    public static function buildMysqlCommand(string $command, array $args = []): string
    {
        $mode = self::getCommandMode();
        $argsStr = !empty($args) ? ' ' . implode(' ', array_map('escapeshellarg', $args)) : '';

        if ($mode === 'docker') {
            $container = self::getMysqlContainer();
            if ($container) {
                return "docker exec -i {$container} {$command}{$argsStr}";
            }
        }

        // Fallback to binary
        return "{$command}{$argsStr}";
    }

    /**
     * Build Redis command (handles Docker)
     * 
     * @param string $command Redis command (e.g., 'redis-cli')
     * @param array $args Command arguments
     * @return string Full command string
     */
    public static function buildRedisCommand(string $command, array $args = []): string
    {
        $mode = self::getCommandMode();
        $argsStr = !empty($args) ? ' ' . implode(' ', array_map('escapeshellarg', $args)) : '';

        if ($mode === 'docker') {
            $container = self::getRedisContainer();
            if ($container) {
                return "docker exec -i {$container} {$command}{$argsStr}";
            }
        }

        // Fallback to binary
        return "{$command}{$argsStr}";
    }

    /**
     * Execute command with proper environment detection
     * 
     * @param string $command Full command string
     * @param string|null $workingDir Working directory
     * @param array &$output Output array (by reference)
     * @param int &$returnVar Return code (by reference)
     * @return bool Success status
     */
    public static function execCommand(string $command, ?string $workingDir = null, array &$output = [], int &$returnVar = 0): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $originalDir = null;
        if ($workingDir) {
            $originalDir = getcwd();
            chdir($workingDir);
        }

        exec($command, $output, $returnVar);

        if ($originalDir) {
            chdir($originalDir);
        }

        return $returnVar === 0;
    }

    /**
     * Execute shell command and return output
     * 
     * @param string $command Full command string
     * @return string|null Output or null on failure
     */
    public static function shellExec(string $command): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $output = @shell_exec($command);
        return $output !== null ? trim($output) : null;
    }
}
