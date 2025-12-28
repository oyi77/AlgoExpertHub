<?php

namespace App\Livewire\Shared;

use Livewire\Component;

class Modal extends Component
{
    public $isOpen = false;
    public $title = '';
    public $content = '';
    public $size = 'md';
    public $showFooter = true;
    public $closeOnBackdrop = true;

    protected $listeners = [
        'openModal' => 'open',
        'closeModal' => 'close'
    ];

    public function open($params = [])
    {
        $this->isOpen = true;
        $this->title = $params['title'] ?? $this->title;
        $this->content = $params['content'] ?? $this->content;
        $this->size = $params['size'] ?? $this->size;
        $this->dispatch('modal-opened');
    }

    public function close()
    {
        $this->isOpen = false;
        $this->dispatch('modal-closed');
    }

    public function confirm()
    {
        $this->dispatch('confirmed');
        $this->close();
    }

    public function render()
    {
        return view('livewire.shared.modal');
    }
}
