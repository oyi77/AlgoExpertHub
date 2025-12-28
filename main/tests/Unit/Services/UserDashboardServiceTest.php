<?php

namespace Tests\Unit\Services;

use App\Models\Deposit;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Withdraw;
use App\Services\UserDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_correct_totals()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        Deposit::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'status' => 1
        ]);

        Withdraw::factory()->create([
            'user_id' => $user->id,
            'withdraw_amount' => 50,
            'status' => 1
        ]);

        Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 25,
            'status' => 1
        ]);

        Ticket::factory()->create([
            'user_id' => $user->id
        ]);

        $service = new UserDashboardService();

        // Act
        $data = $service->dashboard();

        // Assert
        $this->assertEquals(100, $data['totalDeposit']);
        $this->assertEquals(50, $data['totalWithdraw']);
        $this->assertEquals(25, $data['totalPayments']);
        $this->assertEquals(1, $data['totalSupportTickets']);
    }
}
