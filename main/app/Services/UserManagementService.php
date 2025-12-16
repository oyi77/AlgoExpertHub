<?php

namespace App\Services;

use App\Models\User;
use App\Models\PlanSubscription;
use App\Contracts\ServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class UserManagementService extends BaseService implements ServiceInterface
{
    /**
     * Create a new user
     */
    public function create(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            $this->logOperation('create_user', ['email' => $data['email'] ?? null]);
            
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            // Generate unique username if not provided
            if (!isset($data['username'])) {
                $data['username'] = $this->generateUniqueUsername($data['email']);
            }
            
            $user = User::create($data);
            
            // Clear user cache
            $this->invalidateCache(['users']);
            
            return $this->successResponse('User created successfully', [
                'user' => $user->load(['currentplan.plan'])
            ]);
        });
    }

    /**
     * Update an existing user
     */
    public function update(int $id, array $data): array
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $user = User::findOrFail($id);
            
            $this->logOperation('update_user', ['user_id' => $id, 'email' => $user->email]);
            
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            $user->update($data);
            
            // Clear user cache
            $this->invalidateCache(['users', "user:{$id}"]);
            
            return $this->successResponse('User updated successfully', [
                'user' => $user->fresh()->load(['currentplan.plan'])
            ]);
        });
    }

    /**
     * Delete a user
     */
    public function delete(int $id): array
    {
        return $this->executeInTransaction(function () use ($id) {
            $user = User::findOrFail($id);
            
            $this->logOperation('delete_user', ['user_id' => $id, 'email' => $user->email]);
            
            // Check if user has active subscriptions
            $activeSubscriptions = $user->subscriptions()
                ->where('is_current', 1)
                ->where('end_date', '>', now())
                ->count();
            
            if ($activeSubscriptions > 0) {
                return $this->errorResponse(
                    'Cannot delete user with active subscriptions',
                    ['active_subscriptions' => $activeSubscriptions]
                );
            }
            
            $user->delete();
            
            // Clear user cache
            $this->invalidateCache(['users', "user:{$id}"]);
            
            return $this->successResponse('User deleted successfully');
        });
    }

    /**
     * Find a user by ID
     */
    public function find(int $id): array
    {
        try {
            $user = $this->cacheResult("user:{$id}", function () use ($id) {
                return User::with([
                    'currentplan.plan',
                    'subscriptions' => function ($query) {
                        $query->latest()->limit(5);
                    },
                    'subscriptions.plan',
                    'loginSecurity'
                ])->findOrFail($id);
            }, 1800); // 30 minutes cache
            
            return $this->successResponse('User found', ['user' => $user]);
        } catch (\Exception $e) {
            return $this->errorResponse('User not found', [], 404);
        }
    }

    /**
     * Get paginated list of users
     */
    public function list(array $params = []): array
    {
        try {
            $pagination = $this->getPaginationParams($params);
            
            $query = User::with(['currentplan.plan'])
                ->select(['id', 'username', 'email', 'status', 'balance', 'kyc_status', 'created_at']);
            
            // Apply search filters
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Apply status filter
            if (isset($params['status'])) {
                $query->where('status', $params['status']);
            }
            
            // Apply KYC status filter
            if (!empty($params['kyc_status'])) {
                $query->where('kyc_status', $params['kyc_status']);
            }
            
            // Apply date range filter
            if (!empty($params['date_from'])) {
                $query->whereDate('created_at', '>=', $params['date_from']);
            }
            
            if (!empty($params['date_to'])) {
                $query->whereDate('created_at', '<=', $params['date_to']);
            }
            
            // Apply sorting
            $query->orderBy($pagination['sort_by'], $pagination['sort_order']);
            
            $users = $query->paginate($pagination['per_page']);
            
            return $this->successResponse('Users retrieved successfully', [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users');
        }
    }

    /**
     * Change user status (activate/deactivate)
     */
    public function changeStatus(int $id, bool $status): array
    {
        return $this->executeInTransaction(function () use ($id, $status) {
            $user = User::findOrFail($id);
            
            $this->logOperation('change_user_status', [
                'user_id' => $id,
                'old_status' => $user->status,
                'new_status' => $status
            ]);
            
            $user->update(['status' => $status]);
            
            // Clear user cache
            $this->invalidateCache(['users', "user:{$id}"]);
            
            $statusText = $status ? 'activated' : 'deactivated';
            
            return $this->successResponse("User {$statusText} successfully", [
                'user' => $user->fresh()
            ]);
        });
    }

    /**
     * Update user KYC status
     */
    public function updateKycStatus(int $id, string $status, string $reason = null): array
    {
        return $this->executeInTransaction(function () use ($id, $status, $reason) {
            $user = User::findOrFail($id);
            
            $this->logOperation('update_kyc_status', [
                'user_id' => $id,
                'old_status' => $user->kyc_status,
                'new_status' => $status,
                'reason' => $reason
            ]);
            
            $updateData = ['kyc_status' => $status];
            
            if ($reason) {
                $kycInfo = $user->kyc_information ?? [];
                $kycInfo['status_reason'] = $reason;
                $kycInfo['status_updated_at'] = now()->toISOString();
                $updateData['kyc_information'] = $kycInfo;
            }
            
            $user->update($updateData);
            
            // Send notification to user about KYC status change
            // This would typically dispatch a notification job
            
            // Clear user cache
            $this->invalidateCache(['users', "user:{$id}"]);
            
            return $this->successResponse('KYC status updated successfully', [
                'user' => $user->fresh()
            ]);
        });
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        try {
            $stats = $this->cacheResult('user_statistics', function () {
                return [
                    'total_users' => User::count(),
                    'active_users' => User::where('status', 1)->count(),
                    'inactive_users' => User::where('status', 0)->count(),
                    'verified_users' => User::where('is_email_verified', 1)->count(),
                    'kyc_approved' => User::where('kyc_status', 'approved')->count(),
                    'kyc_pending' => User::where('kyc_status', 'pending')->count(),
                    'users_with_subscriptions' => User::whereHas('subscriptions', function ($query) {
                        $query->where('is_current', 1);
                    })->count(),
                    'new_users_today' => User::whereDate('created_at', today())->count(),
                    'new_users_this_week' => User::whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])->count(),
                    'new_users_this_month' => User::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count()
                ];
            }, 600); // 10 minutes cache
            
            return $this->successResponse('User statistics retrieved successfully', $stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user statistics');
        }
    }

    /**
     * Search users by various criteria
     */
    public function search(string $query, array $filters = []): array
    {
        try {
            $users = User::with(['currentplan.plan'])
                ->where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhere('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%");
                });
            
            // Apply additional filters
            $this->applySearchFilters($users, $filters);
            
            $results = $users->limit(50)->get();
            
            return $this->successResponse('Search completed successfully', [
                'users' => $results,
                'count' => $results->count()
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed');
        }
    }

    /**
     * Generate unique username from email
     */
    protected function generateUniqueUsername(string $email): string
    {
        $baseUsername = explode('@', $email)[0];
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        
        $username = $baseUsername;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
}