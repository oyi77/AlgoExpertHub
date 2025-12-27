<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\PlanSubscription;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PlanService
 */
class PlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlanService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_retrieves_all_active_plans(): void
    {
        Plan::factory()->count(3)->create(['status' => 1]);
        Plan::factory()->count(2)->create(['status' => 0]);

        $activePlans = $this->service->getActivePlans();

        $this->assertCount(3, $activePlans);
    }

    /** @test */
    public function it_subscribes_user_to_plan(): void
    {
        $plan = Plan::factory()->create(['price' => 100]);

        $subscription = $this->service->subscribe($this->user->id, $plan->id);

        $this->assertInstanceOf(PlanSubscription::class, $subscription);
        $this->assertEquals($this->user->id, $subscription->user_id);
        $this->assertEquals($plan->id, $subscription->plan_id);
    }

    /** @test */
    public function it_checks_user_plan_access(): void
    {
        $plan = Plan::factory()->create();
        PlanSubscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'is_current' => 1,
        ]);

        $hasAccess = $this->service->userHasAccess($this->user->id, $plan->id);

        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_upgrades_user_plan(): void
    {
        $basicPlan = Plan::factory()->create(['price' => 50]);
        $premiumPlan = Plan::factory()->create(['price' => 100]);

        PlanSubscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $basicPlan->id,
            'is_current' => 1,
        ]);

        $newSubscription = $this->service->upgradePlan($this->user->id, $premiumPlan->id);

        $this->assertEquals($premiumPlan->id, $newSubscription->plan_id);
        $this->assertEquals(1, $newSubscription->is_current);
    }

    /** @test */
    public function it_cancels_user_subscription(): void
    {
        $plan = Plan::factory()->create();
        $subscription = PlanSubscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'is_current' => 1,
        ]);

        $this->service->cancelSubscription($subscription->id);

        $this->assertDatabaseHas('plan_subscriptions', [
            'id' => $subscription->id,
            'is_current' => 0,
        ]);
    }
}
