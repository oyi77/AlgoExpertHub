<?php

namespace App\Console;

use App\Support\AddonRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        if (AddonRegistry::active('multi-channel-signal-addon') && AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'processing')) {
            // Process RSS feeds every 10 minutes
            if (class_exists(\App\Console\Commands\ProcessRssChannels::class) || 
                class_exists(\Addons\MultiChannelSignalAddon\App\Console\Commands\ProcessRssChannels::class)) {
                $schedule->command('channel:process-rss')->everyTenMinutes();
            }

            // Process web scraping channels every minute
            if (class_exists(\App\Console\Commands\ProcessWebScrapeChannels::class) || 
                class_exists(\Addons\MultiChannelSignalAddon\App\Console\Commands\ProcessWebScrapeChannels::class)) {
                $schedule->command('channel:process-web-scrape')->everyMinute();
            }

            // Process Telegram MTProto channels every 5 minutes
            if (class_exists(\Addons\MultiChannelSignalAddon\App\Console\Commands\ProcessTelegramMtprotoChannels::class)) {
                $schedule->command('channel:process-telegram-mtproto')->everyFiveMinutes();
            }

            // Process Trading Bot channels every 2 minutes
            if (class_exists(\Addons\MultiChannelSignalAddon\App\Console\Commands\ProcessTradingBotChannels::class)) {
                $schedule->command('channel:process-trading-bot')->everyTwoMinutes();
            }
        }

        // Trading Bot Signal Addon - Run worker continuously (or use supervisor/systemd)
        if (AddonRegistry::active('trading-bot-signal-addon') && AddonRegistry::moduleEnabled('trading-bot-signal-addon', 'signal_processing')) {
            // Note: For production, run as a daemon/service instead of scheduled command
            // $schedule->command('trading-bot:worker')->everyMinute();
        }

        // Trading Management Addon - Trading Bot Workers
        if (AddonRegistry::active('trading-management-addon')) {
            // Monitor trading bot workers every minute
            if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Jobs\MonitorTradingBotWorkersJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\TradingBot\Jobs\MonitorTradingBotWorkersJob::class)
                    ->everyMinute()
                    ->withoutOverlapping();
            }
        }

        // AlgoExpert++ Addon - System Health
        if (AddonRegistry::active('algoexpert-plus-addon') && AddonRegistry::moduleEnabled('algoexpert-plus-addon', 'health')) {
            // Note: health:snapshot doesn't exist, use horizon:snapshot instead if using Horizon
            // or health:check if using Spatie Health checks
            // Removed health:snapshot as it's not a valid command
        }

        // Horizon Metrics Snapshot - Required for Horizon metrics dashboard
        if (AddonRegistry::active('algoexpert-plus-addon') 
            && AddonRegistry::moduleEnabled('algoexpert-plus-addon', 'queues')
            && env('QUEUE_CONNECTION') === 'redis'
            && class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
            $schedule->command('horizon:snapshot')
                ->everyFiveMinutes()
                ->withoutOverlapping();
            
            // Horizon Monitor - Auto-restart Horizon if it stops
            // Only run if enabled and not using system supervisor
            if (env('HORIZON_CRON_SUPERVISOR_ENABLED', true) && !env('HORIZON_USE_SYSTEM_SUPERVISOR', false)) {
                $scheduleInterval = env('HORIZON_CRON_SUPERVISOR_SCHEDULE', 3);
                $schedule->command('horizon:monitor')
                    ->cron("*/{$scheduleInterval} * * * *")
                    ->withoutOverlapping()
                    ->runInBackground();
            }
        }

        // AlgoExpert++ Addon - System Backup
        if (AddonRegistry::active('algoexpert-plus-addon') && AddonRegistry::moduleEnabled('algoexpert-plus-addon', 'backup')) {
            if (class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
                $schedule->command('backup:run')
                    ->dailyAt('02:00')
                    ->withoutOverlapping();
                $schedule->command('backup:clean')
                    ->weeklyOn(1, '03:00')
                    ->withoutOverlapping();
            }
        }
        // Trading Management Addon - Data Provider Module (Streaming)
        if (AddonRegistry::active('trading-management-addon') && AddonRegistry::moduleEnabled('trading-management-addon', 'data_provider')) {
            // Monitor stream health every 5 minutes
            if (class_exists(\Addons\TradingManagement\Modules\DataProvider\Jobs\MonitorStreamHealthJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\DataProvider\Jobs\MonitorStreamHealthJob::class)
                    ->everyFiveMinutes()
                    ->withoutOverlapping();
            }
        }

        // Trading Management Addon - Execution Module
        if (AddonRegistry::active('trading-management-addon') && AddonRegistry::moduleEnabled('trading-management-addon', 'execution')) {
            // Monitor positions every minute
            if (class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Jobs\MonitorPositionsJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\PositionMonitoring\Jobs\MonitorPositionsJob::class)
                    ->everyMinute()
                    ->withoutOverlapping();
            }

            // Update analytics daily
            if (class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Jobs\UpdateAnalyticsJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\PositionMonitoring\Jobs\UpdateAnalyticsJob::class)
                    ->daily()
                    ->at('00:00');
            }
        }

        // Trading Management Addon - Risk Management Module (Smart Risk)
        if (AddonRegistry::active('trading-management-addon') && AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management')) {
            // Update performance scores daily at 1 AM
            if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Jobs\UpdatePerformanceScoresJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\RiskManagement\Jobs\UpdatePerformanceScoresJob::class)
                    ->daily()
                    ->at('01:00')
                    ->withoutOverlapping();
            }

            // Monitor drawdown every 5 minutes
            if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Jobs\MonitorDrawdownJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\RiskManagement\Jobs\MonitorDrawdownJob::class)
                    ->everyFiveMinutes()
                    ->withoutOverlapping();
            }

            // Retrain models weekly (Sunday at 3 AM)
            if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Jobs\RetrainModelsJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\RiskManagement\Jobs\RetrainModelsJob::class)
                    ->weekly()
                    ->sundays()
                    ->at('03:00')
                    ->withoutOverlapping();
            }
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Safely schedule a command (only if it exists)
     *
     * @param Schedule $schedule
     * @param string $command
     * @param string $frequency
     * @return void
     */
    protected function scheduleCommandSafe(Schedule $schedule, string $command, string $frequency): void
    {
        try {
            $scheduled = $schedule->command($command);
            
            // Apply frequency
            switch ($frequency) {
                case 'everyMinute':
                    $scheduled->everyMinute();
                    break;
                case 'everyTwoMinutes':
                    $scheduled->everyTwoMinutes();
                    break;
                case 'everyFiveMinutes':
                    $scheduled->everyFiveMinutes();
                    break;
                case 'everyTenMinutes':
                    $scheduled->everyTenMinutes();
                    break;
                default:
                    $scheduled->everyMinute();
            }
        } catch (CommandNotFoundException $e) {
            \Log::debug("Command {$command} not found, skipping schedule");
        } catch (\Exception $e) {
            \Log::warning("Failed to schedule command {$command}: " . $e->getMessage());
        }
    }

}
