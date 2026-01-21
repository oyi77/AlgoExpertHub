<?php

namespace Tests\Unit\Services;

use App\Models\Deposit;
use App\Models\Payment;
use App\Models\User;
use App\Models\Withdraw;
use App\Models\UserSignal;
use App\Services\UserDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_aggregation_excludes_old_data()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        // Date 2 years ago (should be excluded)
        $oldDate = Carbon::now()->subYears(2);

        // Date 1 month ago (should be included)
        $recentDate = Carbon::now()->subMonth();

        // --- Deposit ---
        Deposit::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 1,
            'created_at' => $oldDate
        ]);
        Deposit::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'status' => 1,
            'created_at' => $recentDate
        ]);

        // --- Withdraw ---
        Withdraw::factory()->create([
            'user_id' => $user->id,
            'withdraw_amount' => 500,
            'status' => 1,
            'created_at' => $oldDate
        ]);
        Withdraw::factory()->create([
            'user_id' => $user->id,
            'withdraw_amount' => 50,
            'status' => 1,
            'created_at' => $recentDate
        ]);

        $service = new UserDashboardService();

        // Act
        $data = $service->dashboard();

        // Assert
        // The arrays (totalAmount, withdrawTotalAmount, etc.) correspond to the last 12 months.
        // We need to find the index for $recentDate and check the value.
        // And check that the total sum in the graph doesn't include the old amount.
        // But wait, the graph arrays are position-based.

        // $data['months'] contains the month names.
        // Find index of recent month.
        $recentMonthName = $recentDate->monthName;
        $index = array_search($recentMonthName, $data['months']);

        $this->assertNotFalse($index, "Recent month should be in the months array");

        // Check Deposit
        // Old logic (buggy) would include old data if it falls in the same month name?
        // No, old data is 2 years ago. So month name matches.
        // If today is June 2025. 1 month ago is May 2025.
        // 2 years ago is June 2023.
        // If 2 years ago was also May, it would collision.

        // Let's force the old date to have the same MONTH NAME as the recent date to prove the bug.
        // If recent is May, set old to May 2023.

        $oldDateSameMonth = $recentDate->copy()->subYears(2);

        // Update the created entries to use this colliding month
        Deposit::where('amount', 1000)->update(['created_at' => $oldDateSameMonth]);
        Withdraw::where('withdraw_amount', 500)->update(['created_at' => $oldDateSameMonth]);

        // Re-run dashboard
        // We need to clear cache because dashboard uses cache!
        \Cache::flush();
        $data = $service->dashboard();

        $recentMonthIndex = array_search($recentDate->monthName, $data['months']);

        // Deposit: Should be 100. If 1100, then it includes old data.
        $this->assertEquals(100, $data['depositTotalAmount'][$recentMonthIndex], "Deposit graph included old data!");

        // Withdraw: Should be 50. If 550, then it includes old data.
        $this->assertEquals(50, $data['withdrawTotalAmount'][$recentMonthIndex], "Withdraw graph included old data!");
    }
}
