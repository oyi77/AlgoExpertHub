<?php

namespace App\Livewire\Shared;

use Livewire\Component;
use Livewire\WithPagination;

abstract class DataTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $perPage = 15;
    public $selected = [];
    public $selectAll = false;

    // Abstract methods to be implemented by child classes
    abstract public function getQueryProperty();
    abstract public function columns(): array;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getQueryProperty()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        return view('livewire.shared.data-table', [
            'items' => $this->getQueryProperty()->paginate($this->perPage),
            'columns' => $this->columns(),
        ]);
    }
    
    // Helper to get formatted sort icon
    public function getSortIcon($field)
    {
        if ($this->sortField !== $field) {
            return ''; // Default state
        }
        
        return $this->sortDirection === 'asc' ? '↑' : '↓';
    }
}
