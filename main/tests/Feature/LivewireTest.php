<?php

namespace Tests\Feature;

use App\Livewire\TestComponent;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireTest extends TestCase
{
    public function test_component_renders()
    {
        Livewire::test(TestComponent::class)
            ->assertStatus(200);
    }
}
