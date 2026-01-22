<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\TradingBot\BotCrud;

use Tests\TestCase;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_trading_bot(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.store'), [
                'name' => 'Test Bot',
                'type' => 'signal_based',
                'status' => 'created',
                'is_paper_trading' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('trading_bots', [
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'is_paper_trading' => true,
        ]);
    }

    public function test_user_can_view_their_trading_bot(): void
    {
        $bot = TradingBot::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('user.trading-management.trading-bots.show', $bot));

        $response->assertOk();
        $response->assertSee($bot->name);
    }

    public function test_user_can_update_bot_config(): void
    {
        $bot = TradingBot::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->put(route('user.trading-management.trading-bots.update', $bot), [
                'name' => 'Updated Bot',
                'status' => 'paused',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('trading_bots', [
            'id' => $bot->id,
            'name' => 'Updated Bot',
            'status' => 'paused',
        ]);
    }

    public function test_user_can_delete_bot(): void
    {
        $bot = TradingBot::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('user.trading-management.trading-bots.destroy', $bot));

        $response->assertRedirect();
        $this->assertDatabaseMissing('trading_bots', ['id' => $bot->id]);
    }

    public function test_user_cannot_access_other_users_bots(): void
    {
        $otherUser = User::factory()->create();
        $bot = TradingBot::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->get(route('user.trading-management.trading-bots.show', $bot));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_bot_routes(): void
    {
        $bot = TradingBot::factory()->create();

        $response = $this->get(route('user.trading-management.trading-bots.show', $bot));

        $response->assertRedirect('/login');
    }

    public function test_user_can_list_their_bots(): void
    {
        TradingBot::factory()->count(3)->create(['user_id' => $this->user->id]);
        TradingBot::factory()->count(2)->create(); // Other users' bots

        $response = $this->actingAs($this->user)
            ->get(route('user.trading-management.trading-bots.index'));

        $response->assertOk();
        $response->assertSee('trading-bots');
        // Should see only their bots
    }

    public function test_user_can_start_bot(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'created',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.start', $bot));

        $response->assertRedirect();
        $bot->refresh();
        $this->assertEquals('running', $bot->status);
    }

    public function test_user_can_pause_bot(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.pause', $bot));

        $response->assertRedirect();
        $bot->refresh();
        $this->assertEquals('paused', $bot->status);
    }

    public function test_user_can_stop_bot(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.stop', $bot));

        $response->assertRedirect();
        $bot->refresh();
        $this->assertEquals('stopped', $bot->status);
    }

    public function test_bot_validation_requires_name(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.store'), [
                'type' => 'signal_based',
                'status' => 'created',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_bot_validation_requires_type(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('user.trading-management.trading-bots.store'), [
                'name' => 'Test Bot',
                'status' => 'created',
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_inactive_user_cannot_manage_bots(): void
    {
        $this->user->update(['status' => 0]);

        $response = $this->actingAs($this->user)
            ->get(route('user.trading-management.trading-bots.index'));

        $response->assertRedirect('/user/dashboard');
    }
}
