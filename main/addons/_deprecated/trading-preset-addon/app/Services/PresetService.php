<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Addons\TradingPresetAddon\App\Models\TradingPreset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresetService
{
    protected PresetValidationService $validationService;

    public function __construct(PresetValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Create a new preset
     *
     * @param array $data
     * @param User|null $user
     * @return TradingPreset
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $data, ?User $user = null): TradingPreset
    {
        // Validate data
        $this->validationService->validateOrFail($data);

        try {
            DB::beginTransaction();

            // Set creator
            if ($user) {
                $data['created_by_user_id'] = $user->id;
            }

            // Ensure visibility is set
            if (!isset($data['visibility'])) {
                $data['visibility'] = 'PRIVATE';
            }

            // Ensure is_default_template is false for user-created presets
            if ($user && (!isset($data['is_default_template']) || $data['is_default_template'])) {
                $data['is_default_template'] = false;
            }

            $preset = TradingPreset::create($data);

            DB::commit();

            Log::info("Preset created", ['preset_id' => $preset->id, 'user_id' => $user?->id]);

            return $preset;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create preset", ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Update a preset
     *
     * @param TradingPreset $preset
     * @param array $data
     * @param User|null $user
     * @return TradingPreset
     * @throws \Exception
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(TradingPreset $preset, array $data, ?User $user = null): TradingPreset
    {
        // Check permissions
        if ($user && !$preset->canBeEditedBy($user)) {
            throw new \Exception('You do not have permission to edit this preset.');
        }

        // Validate data
        $this->validationService->validateOrFail($data);

        try {
            DB::beginTransaction();

            // Prevent changing is_default_template for non-admins
            if ($user && !($user->is_admin ?? false)) {
                unset($data['is_default_template']);
            }

            $preset->update($data);

            DB::commit();

            Log::info("Preset updated", ['preset_id' => $preset->id, 'user_id' => $user?->id]);

            return $preset->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update preset", ['preset_id' => $preset->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a preset
     *
     * @param TradingPreset $preset
     * @param User|null $user
     * @return bool
     * @throws \Exception
     */
    public function delete(TradingPreset $preset, ?User $user = null): bool
    {
        // Check permissions
        if ($user && !$preset->canBeEditedBy($user)) {
            throw new \Exception('You do not have permission to delete this preset.');
        }

        // Prevent deleting default templates
        if ($preset->is_default_template && (!$user || !($user->is_admin ?? false))) {
            throw new \Exception('Default templates cannot be deleted.');
        }

        try {
            DB::beginTransaction();

            // Check if preset is in use
            $inUse = $this->isPresetInUse($preset);
            if ($inUse) {
                // Soft delete if in use
                $preset->delete();
            } else {
                // Hard delete if not in use
                $preset->forceDelete();
            }

            DB::commit();

            Log::info("Preset deleted", ['preset_id' => $preset->id, 'user_id' => $user?->id, 'soft_delete' => $inUse]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete preset", ['preset_id' => $preset->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Clone a preset for a user
     *
     * @param TradingPreset $preset
     * @param User $user
     * @param string|null $newName
     * @return TradingPreset
     * @throws \Exception
     */
    public function clone(TradingPreset $preset, User $user, ?string $newName = null): TradingPreset
    {
        if (!$preset->canBeClonedBy($user)) {
            throw new \Exception('You do not have permission to clone this preset.');
        }

        try {
            return $preset->cloneFor($user, $newName);
        } catch (\Exception $e) {
            Log::error("Failed to clone preset", ['preset_id' => $preset->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get preset by ID
     *
     * @param int $id
     * @return TradingPreset|null
     */
    public function find(int $id): ?TradingPreset
    {
        return TradingPreset::find($id);
    }

    /**
     * Get preset by ID or fail
     *
     * @param int $id
     * @return TradingPreset
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): TradingPreset
    {
        return TradingPreset::findOrFail($id);
    }

    /**
     * Get all presets for a user
     *
     * @param User $user
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserPresets(User $user, array $filters = [])
    {
        $query = TradingPreset::byUser($user->id);

        if (!empty($filters['enabled'])) {
            $query->enabled();
        }

        if (!empty($filters['symbol'])) {
            $query->bySymbol($filters['symbol']);
        }

        if (!empty($filters['timeframe'])) {
            $query->byTimeframe($filters['timeframe']);
        }

        return $query->get();
    }

    /**
     * Get public presets
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPublicPresets(array $filters = [])
    {
        $query = TradingPreset::public()->enabled();

        if (!empty($filters['symbol'])) {
            $query->bySymbol($filters['symbol']);
        }

        if (!empty($filters['timeframe'])) {
            $query->byTimeframe($filters['timeframe']);
        }

        return $query->get();
    }

    /**
     * Get default templates
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDefaultTemplates()
    {
        return TradingPreset::defaultTemplates()->enabled()->get();
    }

    /**
     * Check if preset is in use
     *
     * @param TradingPreset $preset
     * @return bool
     */
    protected function isPresetInUse(TradingPreset $preset): bool
    {
        // Check execution connections
        if ($preset->executionConnections()->count() > 0) {
            return true;
        }

        // Check copy trading subscriptions
        if ($preset->copyTradingSubscriptions()->count() > 0) {
            return true;
        }

        // Check users with default preset
        if ($preset->usersWithDefault()->count() > 0) {
            return true;
        }

        return false;
    }
}

