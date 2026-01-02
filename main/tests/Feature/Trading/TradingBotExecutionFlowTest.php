<?php

namespace Tests\Feature\Trading;

use Tests\TestCase;
use App\Models\User;
use App\Models\TradingBot;
use App\Models\Signal;
use App\Services\TradingBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;

class TradingBotExecutionFlowTest extends TestCase
{
    // Using RefreshDatabase to ensure clean state
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Additional setup if needed
    }

    public function test_user_can_create_trading_bot()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('trading-bot.store'), [
            'name' => 'Test Bot',
            'pair' => 'BTCUSDT',
            'status' => 'active',
            'settings' => ['risk' => 1]
        ]);

        // Depending on implementation, might redirect or return JSON
        // Assuming redirection to index or show
        // $response->assertRedirect(); 
        
        // For now, let's verify database
        $this->assertDatabaseHas('trading_bots', [
            'user_id' => $user->id,
            'name' => 'Test Bot',
            'pair' => 'BTCUSDT'
        ]);
    }

    public function test_bot_can_be_started_and_stopped()
    {
        $user = User::factory()->create();
        $bot = TradingBot::factory()->create(['user_id' => $user->id, 'status' => 'stopped']);
        
        $this->actingAs($user);

        // Start Bot
        $response = $this->post(route('trading-bot.start', $bot->id));
        $response->assertStatus(200);
        $this->assertEquals('active', $bot->fresh()->status);

        // Stop Bot
        $response = $this->post(route('trading-bot.stop', $bot->id));
        $response->assertStatus(200);
        $this->assertEquals('stopped', $bot->fresh()->status);
    }
}
