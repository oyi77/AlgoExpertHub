<?php

namespace App\Livewire\Admin\Gateways;

use App\Models\Gateway;
use Livewire\Component;

class GatewayManager extends Component
{
    public function getGatewaysProperty()
    {
        return Gateway::latest()->get();
    }

    public function render()
    {
        return view('livewire.admin.gateways.gateway-manager', [
            'gateways' => $this->gateways
        ]);
    }

    // Listener for status update if needed, but ToggleSwitch handles it directly via model binding
    protected $listeners = ['toggle-failed' => 'handleToggleFailure'];

    public function handleToggleFailure()
    {
        // Handle failure if needed, e.g. reload gateways
        $this->dispatch('notify', ['type' => 'error', 'message' => 'Failed to update gateway status.']);
    }
}
