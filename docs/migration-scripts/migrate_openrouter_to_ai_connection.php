<?php

/**
 * Migration Script: OpenRouter Integration → AI Connection Addon
 * 
 * This script migrates OpenRouter configurations from openrouter-integration-addon
 * to the centralized ai-connection-addon.
 * 
 * Usage:
 * php artisan tinker
 * >>> require 'docs/migration-scripts/migrate_openrouter_to_ai_connection.php'
 */

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

try {
    DB::beginTransaction();

    // Check if ai-connection-addon is available
    if (!class_exists(\Addons\AiConnectionAddon\App\Models\AiConnection::class)) {
        throw new \Exception('AI Connection Addon is not installed. Please install it first.');
    }

    // Get OpenRouter configurations from old addon
    $openrouterConfigs = DB::table('openrouter_configurations')->get();

    $migrated = 0;
    $skipped = 0;

    foreach ($openrouterConfigs as $config) {
        // Check if already migrated
        $existing = \Addons\AiConnectionAddon\App\Models\AiConnection::where('provider', 'openrouter')
            ->where('name', 'OpenRouter - ' . ($config->name ?? 'Default'))
            ->first();

        if ($existing) {
            $skipped++;
            continue;
        }

        // Create AI Connection
        \Addons\AiConnectionAddon\App\Models\AiConnection::create([
            'user_id' => $config->user_id ?? null,
            'admin_id' => $config->admin_id ?? null,
            'provider' => 'openrouter',
            'name' => 'OpenRouter - ' . ($config->name ?? 'Default'),
            'api_key' => $config->api_key ?? null,
            'base_url' => $config->base_url ?? 'https://openrouter.ai/api/v1',
            'status' => $config->status ?? 'active',
            'is_active' => $config->is_active ?? true,
            'settings' => [
                'model' => $config->default_model ?? 'openai/gpt-3.5-turbo',
                'max_tokens' => $config->max_tokens ?? 1000,
                'temperature' => $config->temperature ?? 0.7,
            ],
        ]);

        $migrated++;
    }

    DB::commit();

    echo "✅ Migration completed:\n";
    echo "   - Migrated: {$migrated} configurations\n";
    echo "   - Skipped (already exists): {$skipped}\n";
    echo "\n";
    echo "⚠️  Next steps:\n";
    echo "   1. Verify AI Connections in Admin > AI Manager > AI Connections\n";
    echo "   2. Test AI functionality\n";
    echo "   3. Deactivate openrouter-integration-addon after 30 days\n";

} catch (\Exception $e) {
    DB::rollBack();
    Log::error('OpenRouter migration failed', ['error' => $e->getMessage()]);
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   Rollback completed.\n";
}
