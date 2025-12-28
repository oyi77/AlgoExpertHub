<?php

namespace App\Livewire\Shared;

use Livewire\Component;

class Notifications extends Component
{
    public $notifications = [];
    public $position = 'top-right';
    public $timeout = 3000;

    protected $listeners = [
        'notify' => 'addNotification',
    ];

    public function addNotification($params)
    {
        $id = uniqid();
        $type = $params['type'] ?? 'info';
        
        $this->notifications[] = [
            'id' => $id,
            'message' => $params['message'],
            'type' => $type,
            'timeout' => $params['timeout'] ?? $this->timeout,
        ];

        $this->dispatch('notification-shown', ['id' => $id]);
    }

    public function dismiss($id)
    {
        $this->notifications = array_filter($this->notifications, function($notification) use ($id) {
            return $notification['id'] !== $id;
        });

        $this->dispatch('notification-dismissed', ['id' => $id]);
    }

    public function render()
    {
        return view('livewire.shared.notifications');
    }
}
