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
        // Add new column for AI connection reference
        Schema::table('ai_model_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('ai_connection_id')->nullable()->after('enabled');
            
            // Foreign key to AI Connection Addon
            $table->foreign('ai_connection_id')
                  ->references('id')
                  ->on('ai_connections')
                  ->onDelete('restrict');
            
            $table->index('ai_connection_id');
        });

        // Migrate existing profiles to use connections
        $profiles = DB::table('ai_model_profiles')->get();

        foreach ($profiles as $profile) {
            // Find or create matching connection in AI Connection Addon
            $provider = DB::table('ai_providers')
                ->where('slug', $profile->provider)
                ->first();

            if (!$provider) {
                \Log::warning("Provider not found for AI model profile migration", [
                    'profile_id' => $profile->id,
                    'provider' => $profile->provider,
                ]);
                continue;
            }

            // Try to find existing connection with same model
            $existingConnection = DB::table('ai_connections')
                ->where('provider_id', $provider->id)
                ->where('settings->model', $profile->model_name)
                ->first();

            if ($existingConnection) {
                $connectionId = $existingConnection->id;
            } else {
                // Create new connection for this profile
                $credentials = [
                    'api_key' => $profile->api_key_ref, // Store the reference/key
                ];

                $settings = array_merge(
                    json_decode($profile->settings ?? '{}', true),
                    [
                        'model' => $profile->model_name,
                    ]
                );

                try {
                    $connectionId = DB::table('ai_connections')->insertGetId([
                        'provider_id' => $provider->id,
                        'name' => $profile->name . ' (Migrated from AI Trading)',
                        'credentials' => encrypt(json_encode($credentials)),
                        'settings' => json_encode($settings),
                        'status' => $profile->enabled ? 'active' : 'inactive',
                        'priority' => 50,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Failed to create connection for AI model profile", [
                        'profile_id' => $profile->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Update profile with connection reference
            DB::table('ai_model_profiles')
                ->where('id', $profile->id)
                ->update(['ai_connection_id' => $connectionId]);
        }

        // Mark old columns as deprecated (keep for rollback)
        // We'll remove them in a future migration after confirming everything works
        \Log::info("AI Model Profiles migration completed", [
            'total_profiles' => $profiles->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_model_profiles', function (Blueprint $table) {
            $table->dropForeign(['ai_connection_id']);
            $table->dropColumn('ai_connection_id');
        });
    }
};

