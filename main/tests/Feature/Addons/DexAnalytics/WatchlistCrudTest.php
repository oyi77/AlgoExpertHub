<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\DexAnalytics;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class WatchlistCrudTest extends TestCase
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

    public function test_admin_can_view_watchlist_create_form(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.watchlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Trader');
    }

    public function test_admin_can_create_watchlist_entry(): void
    {
        $data = [
            'wallet_address' => '0x1234567890abcdef',
            'platform' => 'gmx',
            'status' => 'active',
            'notes' => 'Test trader',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.dex-analytics.watchlist.store'), $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('dex_trader_watchlist', [
            'wallet_address' => '0x1234567890abcdef',
            'platform' => 'gmx',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_watchlist_edit_form(): void
    {
        $watchlistId = DB::table('dex_trader_watchlist')->insertGetId([
            'wallet_address' => '0xabc123',
            'platform' => 'hyperliquid',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dex-analytics.watchlist.edit', $watchlistId));

        $response->assertStatus(200);
        $response->assertSee('0xabc123');
    }

    public function test_admin_can_update_watchlist_entry(): void
    {
        $watchlistId = DB::table('dex_trader_watchlist')->insertGetId([
            'wallet_address' => '0xold',
            'platform' => 'gmx',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = [
            'wallet_address' => '0xnew',
            'platform' => 'hyperliquid',
            'status' => 'inactive',
            'notes' => 'Updated notes',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.dex-analytics.watchlist.update', $watchlistId), $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('dex_trader_watchlist', [
            'id' => $watchlistId,
            'wallet_address' => '0xnew',
            'platform' => 'hyperliquid',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_watchlist_entry(): void
    {
        $watchlistId = DB::table('dex_trader_watchlist')->insertGetId([
            'wallet_address' => '0xdelete',
            'platform' => 'aster',
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.dex-analytics.watchlist.destroy', $watchlistId));

        $response->assertStatus(302);

        $this->assertDatabaseMissing('dex_trader_watchlist', [
            'id' => $watchlistId,
        ]);
    }

    public function test_admin_can_assign_trader_to_user(): void
    {
        $user = User::factory()->create();

        $data = [
            'wallet_address' => '0xassigned',
            'platform' => 'lighter',
            'status' => 'active',
            'assigned_user_id' => $user->id,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.dex-analytics.watchlist.store'), $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('dex_trader_watchlist', [
            'wallet_address' => '0xassigned',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_validation_requires_wallet_address(): void
    {
        $data = [
            'platform' => 'gmx',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.dex-analytics.watchlist.store'), $data);

        $response->assertSessionHasErrors('wallet_address');
    }

    public function test_validation_requires_platform(): void
    {
        $data = [
            'wallet_address' => '0x123',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.dex-analytics.watchlist.store'), $data);

        $response->assertSessionHasErrors('platform');
    }

    public function test_non_admin_cannot_create_watchlist_entry(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $data = [
            'wallet_address' => '0x123',
            'platform' => 'gmx',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('admin.dex-analytics.watchlist.store'), $data);

        $response->assertStatus(302);
        $response->assertRedirect();
    }
}
