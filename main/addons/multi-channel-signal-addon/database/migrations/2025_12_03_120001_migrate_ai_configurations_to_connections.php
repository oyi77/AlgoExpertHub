<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration moves data from ai_configurations to the centralized AI Connection Addon
        
        // Get all existing AI configurations
        $aiConfigurations = DB::table('ai_configurations')->get();

        foreach ($aiConfigurations as $config) {
            // Get the provider from AI Connection Addon
            $provider = DB::table('ai_providers')
                ->where('slug', $config->provider)
                ->first();

            if (!$provider) {
                // If provider doesn't exist, skip this configuration
                \Log::warning("Provider not found during migration: {$config->provider}");
                continue;
            }

            // Check if connection already exists for this configuration
            $existingConnection = DB::table('ai_connections')
                ->where('provider_id', $provider->id)
                ->where('name', 'like', "%{$config->name}%")
                ->first();

            if ($existingConnection) {
                // Use existing connection
                $connectionId = $existingConnection->id;
            } else {
                // Create new connection in AI Connection Addon
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

                try {
                    $connectionId = DB::table('ai_connections')->insertGetId([
                        'provider_id' => $provider->id,
                        'name' => $config->name . ' (Migrated from Multi-Channel)',
                        'credentials' => encrypt(json_encode($credentials)),
                        'settings' => json_encode($settings),
                        'status' => $config->enabled ? 'active' : 'inactive',
                        'priority' => $config->priority,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Failed to create connection during migration", [
                        'config_id' => $config->id,
                        'provider' => $config->provider,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Create parsing profile that references the connection
            DB::table('ai_parsing_profiles')->insert([
                'channel_source_id' => null, // Global profile (not tied to specific channel)
                'ai_connection_id' => $connectionId,
                'name' => $config->name,
                'parsing_prompt' => null, // Will use default prompts
                'settings' => null, // Uses connection settings
                'priority' => $config->priority,
                'enabled' => $config->enabled,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a column to track migration status (for debugging/rollback)
        Schema::table('ai_configurations', function (Blueprint $table) {
            $table->boolean('migrated')->default(false)->after('enabled');
        });

        // Mark all as migrated
        DB::table('ai_configurations')->update(['migrated' => true]);

        \Log::info("AI Configurations migration completed", [
            'total_configurations' => $aiConfigurations->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migration tracking column
        Schema::table('ai_configurations', function (Blueprint $table) {
            $table->dropColumn('migrated');
        });

        // Note: We don't automatically delete the migrated connections
        // as they might be in use by other features
        // Manual cleanup required if rolling back
        
        \Log::warning("AI Configurations migration rolled back. Manual cleanup of ai_connections and ai_parsing_profiles may be required.");
    }
};

