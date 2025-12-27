<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\User;
use App\Services\UserPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserPlanService $planService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planService = app(UserPlanService::class);
        $this->user = User::factory()->create(['balance' => 1000]);
    }

    /** @test */
    public function it_can_subscribe_to_a_free_plan()
    {
        $plan = Plan::factory()->create([
            'price_type' => 'free',
            'plan_type' => 'unlimited'
        ]);

        $request = new Request(['payment' => $plan->id]);
        
        $this->actingAs($this->user);
        $result = $this->planService->subscribe($request);

        $this->assertEquals('success', $result['type']);
        $this->assertDatabaseHas('plan_subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'is_current' => 1
        ]);
    }

    /** @test */
    public function it_can_subscribe_using_balance()
    {
        $plan = Plan::factory()->create([
            'price' => 100,
            'price_type' => 'paid',
            'plan_type' => 'limited',
            'duration' => 30
        ]);

        $request = new Request([
            'payment' => $plan->id,
            'payment_type' => 'balance'
        ]);
        
        $this->actingAs($this->user);
        $result = $this->planService->subscribe($request);

        $this->assertEquals('success', $result['type']);
        $this->assertEquals(900, $this->user->fresh()->balance);
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'amount' => 100
        ]);
    }

    /** @test */
    public function it_returns_error_for_insufficient_balance()
    {
        $this->user->update(['balance' => 50]);
        
        $plan = Plan::factory()->create([
            'price' => 100,
            'price_type' => 'paid'
        ]);

        $request = new Request([
            'payment' => $plan->id,
            'payment_type' => 'balance'
        ]);
        
        $this->actingAs($this->user);
        $result = $this->planService->subscribe($request);

        $this->assertEquals('error', $result['type']);
        $this->assertEquals('Insufficient Balance', $result['message']);
    }
}
