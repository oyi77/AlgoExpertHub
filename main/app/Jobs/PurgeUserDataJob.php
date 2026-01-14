<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PurgeUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The user ID whose data should be permanently deleted.
     */
    public int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning('PurgeUserDataJob: User not found', ['user_id' => $this->userId]);
            return;
        }

        try {
            $user->forceDelete();

            Log::info('PurgeUserDataJob: User data permanently deleted', [
                'user_id' => $this->userId,
                'username' => $user->username,
                'deleted_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('PurgeUserDataJob: Failed to delete user data', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
