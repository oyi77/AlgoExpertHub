<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UserDashboardService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Carbon\Carbon;

class UserDashboardPerformanceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_dashboard_logic_uses_optimized_flow()
    {
        // Mock GlobalConfiguration
        $globalConfigMock = Mockery::mock('alias:App\Models\GlobalConfiguration');
        $globalConfigMock->shouldReceive('getValue')->andReturn([]);

        // Mock DashboardSignal
        $dashboardSignalMock = Mockery::mock('alias:App\Models\DashboardSignal');
        $dashboardSignalMock->shouldReceive('where')->andReturnSelf();
        $dashboardSignalMock->shouldReceive('latest')->andReturnSelf();
        $dashboardSignalMock->shouldReceive('with')->andReturnSelf();
        $dashboardSignalMock->shouldReceive('paginate')->andReturn([]);

        // Arrange
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 123;
        $user->balance = 1000;

        // Mock relationships invoked on user object
        $user->shouldReceive('currentplan->first')->andReturn(null);
        $user->shouldReceive('transactions->latest->limit->get')->andReturn(collect([]));

        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn(123);

        // Mock Cache
        // We will assert the keys later
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            // We can check keys here
            if (strpos($key, 'udash:totals:') !== false) {
                 return [
                    'currentPlan' => null,
                    'totalDeposit' => 500,
                    'totalWithdraw' => 200,
                    'totalPayments' => 300,
                    'totalSupportTickets' => 2,
                    'recentTransactions' => collect([]),
                ];
            }

            // For the maps
            if (strpos($key, 'udash:paymentAgg:') !== false) {
                 // Return dummy data.
                 // If we optimize, we expect this to be used with integer keys if we change logic
                 // But strictly the test verifies the Service runs.
                 // Let's return a collection indexed by Month Name (current behavior)
                 // or Month Integer (future behavior) depending on what we want to test.
                 // We'll start with Month Name to match current code.
                 // 3 => 100 (Integer Month)
                 return collect([Carbon::now()->month => 100]);
            }

             if (strpos($key, 'udash:withdrawAgg:') !== false) {
                 return collect([]);
            }
             if (strpos($key, 'udash:depositAgg:') !== false) {
                 return collect([]);
            }

            return [];
        });

        // Act
        $service = new UserDashboardService();
        $result = $service->dashboard();

        // Assert
        $this->assertArrayHasKey('totalAmount', $result);
        $this->assertEquals(1000, $result['totalbalance']);

        // Verify we got the data from the map
        // The loop goes backwards 11 months. Current month should be there.
        // If we mocked 'March' => 100, and it's March, we should see 100 in the last element (or close to it)

        $currentMonthIndex = 0; // The loop pushes to array, 0 is 11 months ago, 11 is current month?
        // Code:
        // for ($i = 11; $i >= 0; $i--) { $totalAmount->push(...) }
        // $i=11 is 11 months ago. $i=0 is this month.
        // So the first pushed item is 11 months ago. The last pushed item is current month.

        $this->assertCount(12, $result['totalAmount']);
        $this->assertEquals(100, $result['totalAmount']->last());
    }
}
