<?php

namespace Addons\AiConnectionAddon\App\Console\Commands;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateExistingCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-connections:migrate
                            {--dry-run : Show what would be migrated without actually migrating}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing AI credentials from all features to AI Connection Addon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== AI Credentials Migration Tool ===');
        $this->newLine();

        if (!$force && !$dryRun) {
            if (!$this->confirm('This will migrate AI credentials from all features to the centralized AI Connection Addon. Continue?')) {
                $this->warn('Migration cancelled.');
                return 0;
            }
        }

        $migrated = 0;

        // 1. Migrate Multi-Channel AI Configurations
        $this->info('Migrating Multi-Channel AI Configurations...');
        $migrated += $this->migrateMultiChannelConfigs($dryRun);

        // 2. Migrate AI Trading Model Profiles
        $this->info('Migrating AI Trading Model Profiles...');
        $migrated += $this->migrateAiTradingProfiles($dryRun);

        // 3. Migrate OpenRouter Configurations
        $this->info('Migrating OpenRouter Configurations...');
        $migrated += $this->migrateOpenRouterConfigs($dryRun);

        // 4. Migrate Translation Settings
        $this->info('Migrating Translation Settings...');
        $migrated += $this->migrateTranslationSettings($dryRun);

        $this->newLine();
        if ($dryRun) {
            $this->warn("[DRY RUN] Would have migrated {$migrated} credentials");
        } else {
            $this->info("✓ Successfully migrated {$migrated} credentials");
        }

        return 0;
    }

    protected function migrateMultiChannelConfigs(bool $dryRun): int
    {
        $count = 0;

        if (!Schema::hasTable('ai_configurations')) {
            $this->warn('  • ai_configurations table not found, skipping');
            return 0;
        }

        $configs = DB::table('ai_configurations')
            ->whereNull('migrated')
            ->orWhere('migrated', false)
            ->get();

        $this->info("  • Found {$configs->count()} AI configurations to migrate");

        foreach ($configs as $config) {
            $provider = AiProvider::where('slug', $config->provider)->first();
            
            if (!$provider) {
                $this->warn("  ✗ Provider '{$config->provider}' not found, skipping config: {$config->name}");
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY RUN] Would migrate: {$config->name} ({$config->provider})");
                $count++;
                continue;
            }

            // Create connection
            try {
                $credentials = [
                    'api_key' => $config->api_key ? decrypt($config->api_key) : null,
                    'base_url' => $config->api_url,
                ];

                $settings = array_merge(
                    json_decode($config->settings ?? '{}', true),
                    [
                        'model' => $config->model,
                        'temperature' => $config->temperature,
                        'max_tokens' => $config->max_tokens,
                        'timeout' => $config->timeout,
                    ]
                );

                $connection = AiConnection::create([
                    'provider_id' => $provider->id,
                    'name' => $config->name . ' (Migrated)',
                    'credentials' => $credentials,
                    'settings' => $settings,
                    'status' => $config->enabled ? 'active' : 'inactive',
                    'priority' => $config->priority,
                ]);

                // Create parsing profile
                DB::table('ai_parsing_profiles')->insert([
                    'channel_source_id' => null,
                    'ai_connection_id' => $connection->id,
                    'name' => $config->name,
                    'parsing_prompt' => null,
                    'settings' => null,
                    'priority' => $config->priority,
                    'enabled' => $config->enabled,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Mark as migrated
                DB::table('ai_configurations')
                    ->where('id', $config->id)
                    ->update(['migrated' => true]);

                $this->line("  ✓ Migrated: {$config->name}");
                $count++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to migrate '{$config->name}': {$e->getMessage()}");
            }
        }

        return $count;
    }

    protected function migrateAiTradingProfiles(bool $dryRun): int
    {
        $count = 0;

        if (!Schema::hasTable('ai_model_profiles')) {
            $this->warn('  • ai_model_profiles table not found, skipping');
            return 0;
        }

        $profiles = DB::table('ai_model_profiles')
            ->whereNull('ai_connection_id')
            ->get();

        $this->info("  • Found {$profiles->count()} AI model profiles to migrate");

        foreach ($profiles as $profile) {
            $provider = AiProvider::where('slug', $profile->provider)->first();
            
            if (!$provider) {
                $this->warn("  ✗ Provider '{$profile->provider}' not found, skipping profile: {$profile->name}");
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY RUN] Would migrate: {$profile->name} ({$profile->provider})");
                $count++;
                continue;
            }

            try {
                $credentials = [
                    'api_key' => $profile->api_key_ref,
                ];

                $settings = array_merge(
                    json_decode($profile->settings ?? '{}', true),
                    [
                        'model' => $profile->model_name,
                    ]
                );

                $connection = AiConnection::create([
                    'provider_id' => $provider->id,
                    'name' => $profile->name . ' (AI Trading)',
                    'credentials' => $credentials,
                    'settings' => $settings,
                    'status' => $profile->enabled ? 'active' : 'inactive',
                    'priority' => 50,
                ]);

                // Update profile with connection reference
                DB::table('ai_model_profiles')
                    ->where('id', $profile->id)
                    ->update(['ai_connection_id' => $connection->id]);

                $this->line("  ✓ Migrated: {$profile->name}");
                $count++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to migrate '{$profile->name}': {$e->getMessage()}");
            }
        }

        return $count;
    }

    protected function migrateOpenRouterConfigs(bool $dryRun): int
    {
        $count = 0;

        if (!Schema::hasTable('openrouter_configurations')) {
            $this->warn('  • openrouter_configurations table not found, skipping');
            return 0;
        }

        $configs = DB::table('openrouter_configurations')
            ->whereNull('ai_connection_id')
            ->get();

        $this->info("  • Found {$configs->count()} OpenRouter configurations to migrate");

        foreach ($configs as $config) {
            $provider = AiProvider::where('slug', 'openrouter')->first();
            
            if (!$provider) {
                $this->warn("  ✗ OpenRouter provider not found");
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY RUN] Would migrate: {$config->name}");
                $count++;
                continue;
            }

            try {
                // Decrypt API key
                $apiKey = !empty($config->api_key) ? decrypt($config->api_key) : null;
                
                if (!$apiKey) {
                    $this->warn("  ✗ No API key found for: {$config->name}");
                    continue;
                }

                $credentials = ['api_key' => $apiKey];

                $settings = [
                    'model' => $config->model_id,
                    'temperature' => $config->temperature,
                    'max_tokens' => $config->max_tokens,
                    'timeout' => $config->timeout,
                    'site_url' => $config->site_url,
                    'site_name' => $config->site_name,
                ];

                $connection = AiConnection::create([
                    'provider_id' => $provider->id,
                    'name' => $config->name . ' (OpenRouter)',
                    'credentials' => $credentials,
                    'settings' => $settings,
                    'status' => $config->enabled ? 'active' : 'inactive',
                    'priority' => $config->priority,
                ]);

                // Update OpenRouter config with connection reference
                DB::table('openrouter_configurations')
                    ->where('id', $config->id)
                    ->update(['ai_connection_id' => $connection->id]);

                $this->line("  ✓ Migrated: {$config->name}");
                $count++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to migrate '{$config->name}': {$e->getMessage()}");
            }
        }

        return $count;
    }

    protected function migrateTranslationSettings(bool $dryRun): int
    {
        // Check if env-based OpenAI config exists and translation settings don't
        $hasEnvConfig = !empty(config('services.openai.key'));
        $hasTranslationSettings = DB::table('translation_settings')->exists();

        if (!$hasEnvConfig || $hasTranslationSettings) {
            $this->warn('  • Translation settings already configured or no env config found, skipping');
            return 0;
        }

        $this->info('  • Found OpenAI config in services, creating translation settings');

        if ($dryRun) {
            $this->line('  [DRY RUN] Would create OpenAI connection for translations');
            return 1;
        }

        try {
            $provider = AiProvider::where('slug', 'openai')->first();
            
            if (!$provider) {
                $this->warn('  ✗ OpenAI provider not found');
                return 0;
            }

            // Create connection from env config
            $connection = AiConnection::create([
                'provider_id' => $provider->id,
                'name' => 'OpenAI for Translations (Migrated from ENV)',
                'credentials' => [
                    'api_key' => config('services.openai.key'),
                ],
                'settings' => [
                    'model' => config('services.openai.model', 'gpt-3.5-turbo'),
                    'temperature' => 0.3,
                    'max_tokens' => 1000,
                ],
                'status' => 'active',
                'priority' => 1,
            ]);

            // Create translation settings
            DB::table('translation_settings')->insert([
                'ai_connection_id' => $connection->id,
                'batch_size' => 10,
                'delay_between_requests_ms' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->line('  ✓ Created translation settings with OpenAI connection');
            return 1;
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to migrate translation settings: ' . $e->getMessage());
            return 0;
        }
    }
}

