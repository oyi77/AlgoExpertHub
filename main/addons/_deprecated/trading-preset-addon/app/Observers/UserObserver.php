<?php

namespace Addons\TradingPresetAddon\App\Observers;

use Addons\TradingPresetAddon\App\Models\TradingPreset;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        try {
            // Get Conservative Scalper preset as default
            $defaultPreset = TradingPreset::defaultTemplates()
                ->enabled()
                ->where('name', 'Conservative Scalper')
                ->first();

            // If Conservative Scalper not found, get first default template
            if (!$defaultPreset) {
                $defaultPreset = TradingPreset::defaultTemplates()
                    ->enabled()
                    ->orderBy('id')
                    ->first();
            }

            if ($defaultPreset) {
                $user->default_preset_id = $defaultPreset->id;
                $user->saveQuietly(); // Use saveQuietly to avoid triggering events again

                Log::info("Default preset assigned to new user", [
                    'user_id' => $user->id,
                    'preset_id' => $defaultPreset->id,
                    'preset_name' => $defaultPreset->name,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to assign default preset to new user", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

