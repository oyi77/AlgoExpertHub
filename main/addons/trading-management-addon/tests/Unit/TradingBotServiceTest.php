<?php

namespace Addons\TradingManagement\Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * TradingBotService Unit Tests
 */
class TradingBotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TradingBotService $service;
    protected User $user;
    protected ExchangeConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TradingBotService::class);
        $this->user = User::factory()->create();
        $this->connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_create_bot(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => $this->connection->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_active' => true,
        ];

        $bot = $this->service->create($data);

        $this->assertInstanceOf(TradingBot::class, $bot);
        $this->assertEquals('Test Bot', $bot->name);
        $this->assertEquals('SIGNAL_BASED', $bot->trading_mode);
    }

    public function test_validate_for_start(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'status' => 'stopped',
            'is_active' => true,
        ]);

        $result = $this->service->validateForStart($bot);
        $this->assertTrue($result['valid']);
    }

    public function test_start_bot(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'status' => 'stopped',
            'is_active' => true,
        ]);

        $this->service->start($bot, $this->user->id);
        $this->assertEquals('running', $bot->fresh()->status);
    }

    public function test_stop_bot(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'status' => 'running',
            'is_active' => true,
        ]);

        $this->service->stop($bot, $this->user->id);
        $this->assertEquals('stopped', $bot->fresh()->status);
    }
}
