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
        // Add ai_connection_id column to reference centralized AI Connection
        Schema::table('openrouter_configurations', function (Blueprint $table) {
            $table->unsignedBigInteger('ai_connection_id')->nullable()->after('enabled');
            
            // Foreign key to AI Connection Addon
            $table->foreign('ai_connection_id')
                  ->references('id')
                  ->on('ai_connections')
                  ->onDelete('restrict');
            
            $table->index('ai_connection_id');
        });

        // Migrate existing OpenRouter configurations to AI Connection Addon
        $configs = DB::table('openrouter_configurations')->get();
        
        // Get or create OpenRouter provider
        $provider = DB::table('ai_providers')->where('slug', 'openrouter')->first();
        
        if (!$provider) {
            \Log::error("OpenRouter provider not found in ai_providers table");
            return;
        }

        foreach ($configs as $config) {
            // Check if connection already exists for this config
            $existingConnection = DB::table('ai_connections')
                ->where('provider_id', $provider->id)
                ->where('name', 'like', "%{$config->name}%")
                ->first();

            if ($existingConnection) {
                $connectionId = $existingConnection->id;
            } else {
                // Decrypt existing API key
                try {
                    $apiKey = !empty($config->api_key) ? decrypt($config->api_key) : null;
                } catch (\Exception $e) {
                    \Log::warning("Could not decrypt OpenRouter API key for config {$config->id}");
                    continue;
                }

                if (!$apiKey) {
                    \Log::warning("No API key found for OpenRouter config {$config->id}");
                    continue;
                }

                // Create connection in AI Connection Addon
                $credentials = [
                    'api_key' => $apiKey,
                ];

                $settings = [
                    'model' => $config->model_id,
                    'temperature' => $config->temperature,
                    'max_tokens' => $config->max_tokens,
                    'timeout' => $config->timeout,
                    'site_url' => $config->site_url,
                    'site_name' => $config->site_name,
                ];

                try {
                    $connectionId = DB::table('ai_connections')->insertGetId([
                        'provider_id' => $provider->id,
                        'name' => $config->name . ' (OpenRouter)',
                        'credentials' => encrypt(json_encode($credentials)),
                        'settings' => json_encode($settings),
                        'status' => $config->enabled ? 'active' : 'inactive',
                        'priority' => $config->priority,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Failed to create AI connection for OpenRouter config", [
                        'config_id' => $config->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Update OpenRouter configuration with connection reference
            DB::table('openrouter_configurations')
                ->where('id', $config->id)
                ->update(['ai_connection_id' => $connectionId]);
        }

        \Log::info("OpenRouter configurations migrated to AI Connection Addon", [
            'total_configs' => $configs->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('openrouter_configurations', function (Blueprint $table) {
            $table->dropForeign(['ai_connection_id']);
            $table->dropColumn('ai_connection_id');
        });
    }
};

