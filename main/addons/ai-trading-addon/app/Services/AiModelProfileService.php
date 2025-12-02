<?php

namespace Addons\AiTradingAddon\App\Services;

use Addons\AiTradingAddon\App\Models\AiModelProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiModelProfileService
{
    public function create(array $data, ?User $user = null): AiModelProfile
    {
        try {
            DB::beginTransaction();

            if ($user) {
                $data['created_by_user_id'] = $user->id;
            }

            // Ensure visibility is set
            if (!isset($data['visibility'])) {
                $data['visibility'] = 'PRIVATE';
            }

            // Parse settings if string
            if (isset($data['settings']) && is_string($data['settings'])) {
                $data['settings'] = json_decode($data['settings'], true);
            }

            $profile = AiModelProfile::create($data);

            DB::commit();

            Log::info("AI Model Profile created", ['profile_id' => $profile->id, 'user_id' => $user?->id]);

            return $profile;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create AI model profile", ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    public function update(AiModelProfile $profile, array $data, ?User $user = null): AiModelProfile
    {
        try {
            DB::beginTransaction();

            // Parse settings if string
            if (isset($data['settings']) && is_string($data['settings'])) {
                $data['settings'] = json_decode($data['settings'], true);
            }

            $profile->update($data);

            DB::commit();

            Log::info("AI Model Profile updated", ['profile_id' => $profile->id, 'user_id' => $user?->id]);

            return $profile;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update AI model profile", ['error' => $e->getMessage(), 'profile_id' => $profile->id]);
            throw $e;
        }
    }

    public function delete(AiModelProfile $profile, ?User $user = null): bool
    {
        try {
            DB::beginTransaction();

            // Check if profile is used by any presets
            $presetsCount = $profile->tradingPresets()->count();
            if ($presetsCount > 0) {
                throw new \Exception("Cannot delete profile: {$presetsCount} preset(s) are using it");
            }

            $profile->delete();

            DB::commit();

            Log::info("AI Model Profile deleted", ['profile_id' => $profile->id, 'user_id' => $user?->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete AI model profile", ['error' => $e->getMessage(), 'profile_id' => $profile->id]);
            throw $e;
        }
    }

    public function clone(AiModelProfile $profile, User $user): AiModelProfile
    {
        try {
            DB::beginTransaction();

            $cloned = $profile->replicate();
            $cloned->created_by_user_id = $user->id;
            $cloned->visibility = 'PRIVATE';
            $cloned->name = $profile->name . ' (Copy)';
            $cloned->save();

            DB::commit();

            Log::info("AI Model Profile cloned", [
                'original_id' => $profile->id,
                'cloned_id' => $cloned->id,
                'user_id' => $user->id,
            ]);

            return $cloned;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to clone AI model profile", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function findOrFail(int $id): AiModelProfile
    {
        return AiModelProfile::findOrFail($id);
    }
}

