<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Shared\DataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsersTable extends DataTable
{
    public function getQueryProperty()
    {
        return User::query()
            ->when($this->search, function (Builder $query) {
                $query->where(function ($q) {
                    $q->where('username', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'id',
                'label' => 'ID',
                'sortable' => true
            ],
            [
                'key' => 'username',
                'label' => 'Username',
                'sortable' => true,
                'view' => 'livewire.admin.users.columns.username', // Custom view for avatar+username
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'sortable' => true
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'view' => 'livewire.admin.users.columns.status', // Badge view
            ],
            [
                'key' => 'kyc_status',
                'label' => 'KYC',
                'sortable' => true,
                'view' => 'livewire.admin.users.columns.kyc', // Badge view
            ],
            [
                'key' => 'created_at',
                'label' => 'Joined At',
                'sortable' => true,
                'callback' => function($row) {
                    return $row->created_at->format('d M Y');
                }
            ],
        ];
    }

    public function deleteUser($id)
    {
        // In a real app, use the service layer: $this->userService->deleteUser($id);
        // For pilot, direct model access if service not fully ready for this matching signature
        $user = User::findOrFail($id);
        $user->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'User deleted successfully'
        ]);
        
        $this->dispatch('user-deleted'); // Refresh or other side effects
    }

    public function bulkDelete()
    {
        if (empty($this->selected)) {
            return;
        }

        User::whereIn('id', $this->selected)->delete();
        
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Selected users deleted successfully'
        ]);
    }
}
