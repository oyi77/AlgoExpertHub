<?php

namespace Tests\Feature;

use App\Models\Signal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_signals_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.signals'));

        $response->assertStatus(200);
        $response->assertViewIs(Helper::themeView('user.signals'));
    }

    /** @test */
    public function user_can_view_signal_details()
    {
        $signal = Signal::factory()->create(['is_published' => 1]);
        
        // Setup dashboard signal for user
        \App\Models\DashboardSignal::create([
            'user_id' => $this->user->id,
            'signal_id' => $signal->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.signal.details', $signal->id));

        $response->assertStatus(200);
        $response->assertSee($signal->title);
    }
}
