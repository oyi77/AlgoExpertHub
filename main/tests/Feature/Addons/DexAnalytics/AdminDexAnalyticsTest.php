<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AdminDexAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_access_dex_analytics_dashboard(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.dashboard'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_watchlist_index(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.watchlist.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_analytics_index(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.analytics.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_leaderboards(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.leaderboards.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_settings(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.settings.index'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.dex-analytics.dashboard'));

        $response->assertStatus(302);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.dex-analytics.dashboard'));

        $response->assertStatus(302);
    }
}
