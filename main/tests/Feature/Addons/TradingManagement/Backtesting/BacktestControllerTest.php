<?php

namespace Tests\Feature\Addons\TradingManagement\Backtesting;

use Tests\TestCase;
use App\Models\User;
use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BacktestControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $preset;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->preset = TradingPreset::factory()->create([
            'created_by_user_id' => $this->user->id,
            'name' => 'Test Preset',
        ]);
    }

    /** @test */
    public function user_can_view_backtesting_index_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.index'));

        $response->assertStatus(200);
        $response->assertViewIs('user.backtesting.index');
        $response->assertViewHas('backtests');
    }

    /** @test */
    public function user_can_view_create_backtest_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.create'));

        $response->assertStatus(200);
        $response->assertViewIs('user.backtesting.create');
        $response->assertViewHas(['presets', 'symbols', 'timeframes']);
    }

    /** @test */
    public function user_can_create_backtest_with_valid_data()
    {
        $data = [
            'name' => 'Test Backtest',
            'description' => 'Testing BTC strategy',
            'symbol' => 'BTCUSDT',
            'timeframe' => '1h',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->subDay()->format('Y-m-d'),
            'initial_balance' => 10000,
            'preset_id' => $this->preset->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('user.backtesting.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('backtests', [
            'user_id' => $this->user->id,
            'name' => 'Test Backtest',
            'symbol' => 'BTCUSDT',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function backtest_creation_fails_with_invalid_date_range()
    {
        $data = [
            'name' => 'Test Backtest',
            'symbol' => 'BTCUSDT',
            'timeframe' => '1h',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->subMonths(1)->format('Y-m-d'), // End before start
            'initial_balance' => 10000,
            'preset_id' => $this->preset->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('user.backtesting.store'), $data);

        $response->assertSessionHasErrors('end_date');
    }

    /** @test */
    public function backtest_creation_fails_with_date_range_exceeding_2_years()
    {
        $data = [
            'name' => 'Test Backtest',
            'symbol' => 'BTCUSDT',
            'timeframe' => '1h',
            'start_date' => now()->subYears(3)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'initial_balance' => 10000,
            'preset_id' => $this->preset->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('user.backtesting.store'), $data);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function backtest_creation_requires_preset()
    {
        $data = [
            'name' => 'Test Backtest',
            'symbol' => 'BTCUSDT',
            'timeframe' => '1h',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'initial_balance' => 10000,
            // Missing preset_id
        ];

        $response = $this->actingAs($this->user)
            ->post(route('user.backtesting.store'), $data);

        $response->assertSessionHasErrors('preset_id');
    }

    /** @test */
    public function user_can_view_their_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.show', $backtest->id));

        $response->assertStatus(200);
        $response->assertViewIs('user.backtesting.show');
        $response->assertViewHas('backtest');
    }

    /** @test */
    public function user_cannot_view_other_users_backtest()
    {
        $otherUser = User::factory()->create();
        $backtest = Backtest::factory()->create([
            'user_id' => $otherUser->id,
            'preset_id' => $this->preset->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.show', $backtest->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function user_can_delete_their_pending_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('user.backtesting.destroy', $backtest->id));

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('backtests', ['id' => $backtest->id]);
    }

    /** @test */
    public function user_cannot_delete_running_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('user.backtesting.destroy', $backtest->id));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('backtests', ['id' => $backtest->id]);
    }

    /** @test */
    public function user_can_run_pending_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.backtesting.run', $backtest->id));

        $response->assertJson(['success' => true]);
        // Job should be dispatched (we'd need to mock Queue to test this properly)
    }

    /** @test */
    public function user_cannot_run_already_running_backtest()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.backtesting.run', $backtest->id));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function user_can_get_backtest_status()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'running',
            'progress_percent' => 45,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.status', $backtest->id));

        $response->assertJson([
            'success' => true,
            'status' => 'running',
            'progress' => 45,
            'is_running' => true,
            'is_completed' => false,
        ]);
    }

    /** @test */
    public function index_page_filters_by_status()
    {
        Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'completed',
        ]);
        
        Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.index', ['status' => 'completed']));

        $response->assertStatus(200);
        $backtests = $response->viewData('backtests');
        $this->assertEquals(1, $backtests->count());
        $this->assertEquals('completed', $backtests->first()->status);
    }

    /** @test */
    public function backtest_with_results_displays_metrics()
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'completed',
        ]);

        $result = BacktestResult::factory()->create([
            'backtest_id' => $backtest->id,
            'total_trades' => 100,
            'winning_trades' => 65,
            'win_rate' => 65.00,
            'net_profit' => 5000.00,
            'return_percent' => 50.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.backtesting.show', $backtest->id));

        $response->assertStatus(200);
        $response->assertSee('$5,000.00'); // Net profit
        $response->assertSee('65.00%'); // Win rate
    }
}
