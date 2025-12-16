<?php

namespace Tests\Property;

use Tests\TestCase;
use App\Services\QueryOptimizationService;
use App\Models\Signal;
use App\Models\User;
use App\Models\Plan;
use App\Models\PlanSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class NPlusOneQueryPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected $queryOptimizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryOptimizationService = app(QueryOptimizationService::class);
    }

    /**
     * Feature: platform-optimization-improvements, Property 3: Query Optimization
     * For any database operation, N+1 queries should be eliminated through proper eager loading
     * 
     * @test
     */
    public function testSignalQueryOptimization()
    {
        $signalCounts = [1, 5, 10, 15];
        
        foreach ($signalCounts as $signalCount) {
            // Create test data
            $market = \App\Models\Market::factory()->create();
            $currencyPair = \App\Models\CurrencyPair::factory()->create();
            $timeFrame = \App\Models\TimeFrame::factory()->create();
            
            $signals = [];
            for ($i = 0; $i < $signalCount; $i++) {
                $signals[] = Signal::factory()->create([
                    'market_id' => $market->id,
                    'currency_pair_id' => $currencyPair->id,
                    'time_frame_id' => $timeFrame->id,
                    'is_published' => 1
                ]);
            }
            
            // Enable query logging
            DB::enableQueryLog();
            
            // Test optimized query
            $optimizedSignals = $this->queryOptimizationService->getOptimizedSignals([
                'is_published' => 1
            ])->get();
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // With proper eager loading, we should have a limited number of queries
            // regardless of the number of signals
            $this->assertLessThanOrEqual(5, count($queries), 
                "Should have at most 5 queries with eager loading, got " . count($queries) . " queries for {$signalCount} signals");
            
            // Verify we got the correct number of signals
            $this->assertCount($signalCount, $optimizedSignals);
            
            // Verify relationships are loaded (no additional queries when accessing)
            DB::enableQueryLog();
            
            foreach ($optimizedSignals as $signal) {
                // Accessing these relationships should not trigger additional queries
                $pairName = $signal->pair->name;
                $timeName = $signal->time->name;
                $marketName = $signal->market->name;
                
                $this->assertNotNull($pairName);
                $this->assertNotNull($timeName);
                $this->assertNotNull($marketName);
            }
            
            $relationshipQueries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should have no additional queries when accessing eager-loaded relationships
            $this->assertEmpty($relationshipQueries, 
                "Should have no additional queries when accessing eager-loaded relationships");
        }
    }

    /**
     * Test user subscription query optimization
     * 
     * @test
     */
    public function testUserSubscriptionQueryOptimization()
    {
        $userCounts = [1, 3, 5];
        
        foreach ($userCounts as $userCount) {
            // Create test data
            $plan = Plan::factory()->create();
            $users = [];
            
            for ($i = 0; $i < $userCount; $i++) {
                $user = User::factory()->create();
                PlanSubscription::factory()->create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'is_current' => 1,
                    'end_date' => now()->addDays(30)
                ]);
                $users[] = $user;
            }
            
            // Test optimized query for active subscriptions
            DB::enableQueryLog();
            
            $activeSubscriptions = $this->queryOptimizationService->getActiveSubscriptions()->get();
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should have limited queries regardless of user count
            $this->assertLessThanOrEqual(3, count($queries), 
                "Should have at most 3 queries for active subscriptions, got " . count($queries) . " queries for {$userCount} users");
            
            // Verify we got the correct subscriptions
            $this->assertCount($userCount, $activeSubscriptions);
            
            // Test accessing relationships without additional queries
            DB::enableQueryLog();
            
            foreach ($activeSubscriptions as $subscription) {
                $userName = $subscription->user->username;
                $planName = $subscription->plan->name;
                
                $this->assertNotNull($userName);
                $this->assertNotNull($planName);
            }
            
            $relationshipQueries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should have no additional queries for eager-loaded relationships
            $this->assertEmpty($relationshipQueries, 
                "Should have no additional queries when accessing user and plan relationships");
        }
    }

    /**
     * Test dashboard signals query optimization
     * 
     * @test
     */
    public function testDashboardSignalsQueryOptimization()
    {
        $signalCounts = [1, 10, 20];
        
        foreach ($signalCounts as $signalCount) {
            // Create test data
            $user = User::factory()->create();
            $market = \App\Models\Market::factory()->create();
            $currencyPair = \App\Models\CurrencyPair::factory()->create();
            $timeFrame = \App\Models\TimeFrame::factory()->create();
            
            for ($i = 0; $i < $signalCount; $i++) {
                $signal = Signal::factory()->create([
                    'market_id' => $market->id,
                    'currency_pair_id' => $currencyPair->id,
                    'time_frame_id' => $timeFrame->id,
                    'is_published' => 1
                ]);
                
                \App\Models\DashboardSignal::create([
                    'user_id' => $user->id,
                    'signal_id' => $signal->id
                ]);
            }
            
            // Test optimized dashboard signals query
            DB::enableQueryLog();
            
            $dashboardSignals = $this->queryOptimizationService->getOptimizedDashboardSignals($user->id, 20)->get();
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should have limited queries regardless of signal count
            $this->assertLessThanOrEqual(4, count($queries), 
                "Should have at most 4 queries for dashboard signals, got " . count($queries) . " queries for {$signalCount} signals");
            
            // Verify we got signals (up to the limit)
            $expectedCount = min($signalCount, 20);
            $this->assertCount($expectedCount, $dashboardSignals);
            
            // Test accessing nested relationships without additional queries
            DB::enableQueryLog();
            
            foreach ($dashboardSignals as $dashboardSignal) {
                if ($dashboardSignal->signal) {
                    $signalTitle = $dashboardSignal->signal->title;
                    $pairName = $dashboardSignal->signal->pair->name;
                    $timeName = $dashboardSignal->signal->time->name;
                    $marketName = $dashboardSignal->signal->market->name;
                    
                    $this->assertNotNull($signalTitle);
                    $this->assertNotNull($pairName);
                    $this->assertNotNull($timeName);
                    $this->assertNotNull($marketName);
                }
            }
            
            $relationshipQueries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should have no additional queries for nested relationships
            $this->assertEmpty($relationshipQueries, 
                "Should have no additional queries when accessing nested signal relationships");
        }
    }

    /**
     * Test query count consistency across different data sizes
     * 
     * @test
     */
    public function testQueryCountConsistency()
    {
        $testCases = [
            ['small' => 2, 'large' => 15],
            ['small' => 3, 'large' => 25],
        ];
        
        foreach ($testCases as $testCase) {
            $smallCount = $testCase['small'];
            $largeCount = $testCase['large'];
            // Create small dataset
            $market = \App\Models\Market::factory()->create();
            $currencyPair = \App\Models\CurrencyPair::factory()->create();
            $timeFrame = \App\Models\TimeFrame::factory()->create();
            
            for ($i = 0; $i < $smallCount; $i++) {
                Signal::factory()->create([
                    'market_id' => $market->id,
                    'currency_pair_id' => $currencyPair->id,
                    'time_frame_id' => $timeFrame->id,
                    'is_published' => 1
                ]);
            }
            
            // Test query count for small dataset
            DB::enableQueryLog();
            $this->queryOptimizationService->getOptimizedSignals(['is_published' => 1])->get();
            $smallQueries = count(DB::getQueryLog());
            DB::disableQueryLog();
            
            // Create larger dataset
            for ($i = $smallCount; $i < $largeCount; $i++) {
                Signal::factory()->create([
                    'market_id' => $market->id,
                    'currency_pair_id' => $currencyPair->id,
                    'time_frame_id' => $timeFrame->id,
                    'is_published' => 1
                ]);
            }
            
            // Test query count for larger dataset
            DB::enableQueryLog();
            $this->queryOptimizationService->getOptimizedSignals(['is_published' => 1])->get();
            $largeQueries = count(DB::getQueryLog());
            DB::disableQueryLog();
            
            // Query count should be the same regardless of dataset size (no N+1)
            $this->assertEquals($smallQueries, $largeQueries, 
                "Query count should be consistent regardless of dataset size. Small: {$smallQueries}, Large: {$largeQueries}");
        }
    }
}