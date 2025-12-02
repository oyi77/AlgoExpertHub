<?php

namespace Addons\TradingPresetAddon\App\Observers;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Illuminate\Support\Facades\Log;

class ExecutionConnectionObserver
{
    /**
     * Handle the ExecutionConnection "created" event.
     *
     * @param ExecutionConnection $connection
     * @return void
     */
    public function created(ExecutionConnection $connection): void
    {
        try {
            // Only auto-assign preset for user-owned connections
            if (!$connection->user_id) {
                return;
            }

            // If preset already assigned, don't override
            if ($connection->preset_id) {
                return;
            }

            // Get user's default preset
            $user = $connection->user;
            if ($user && $user->default_preset_id) {
                $connection->preset_id = $user->default_preset_id;
                $connection->saveQuietly(); // Use saveQuietly to avoid triggering events again

                Log::info("Default preset auto-assigned to new connection", [
                    'connection_id' => $connection->id,
                    'user_id' => $user->id,
                    'preset_id' => $user->default_preset_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to auto-assign preset to new connection", [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

