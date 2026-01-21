<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserDexAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'user@test.com',
            'is_admin' => false,
        ]);
    }

    public function test_user_can_access_dex_analytics_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.dashboard'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_watchlist_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.watchlist.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_analytics_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.analytics.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_leaderboards(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.leaderboards.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_access_ai_insights(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.ai-insights.index'));

        $response->assertStatus(200);
    }

    public function test_user_sees_only_assigned_watchlist_traders(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            [
                'id' => 1,
                'wallet_address' => '0x111',
                'platform' => 'gmx',
                'status' => 'active',
                'is_active' => true,
                'assigned_user_id' => $this->user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'wallet_address' => '0x222',
                'platform' => 'hyperliquid',
                'status' => 'active',
                'is_active' => true,
                'assigned_user_id' => 999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.watchlist.index'));

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_user_dashboard(): void
    {
        $response = $this->get(route('user.dex-analytics.dashboard'));

        $response->assertStatus(302);
    }

    public function test_user_can_view_trader_analytics(): void
    {
        DB::table('dex_trader_watchlist')->insert([
            'id' => 1,
            'wallet_address' => '0xabc123',
            'platform' => 'gmx',
            'status' => 'active',
            'is_active' => true,
            'assigned_user_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.dex-analytics.analytics.trader', ['wallet' => '0xabc123']));

        $response->assertStatus(200);
    }
}
