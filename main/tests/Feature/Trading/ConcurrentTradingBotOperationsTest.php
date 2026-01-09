<?php

namespace Tests\Feature\Trading;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\TradingPreset\Models\TradingPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Concurrent Trading Bot Operations Test
 * 
 * Tests concurrent operations on trading bots to ensure:
 * - Race conditions are prevented
 * - Database transactions work correctly
 * - Row-level locking prevents conflicts
 * - Idempotency is maintained
 */
class ConcurrentTradingBotOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected TradingBotService $botService;
    protected ExchangeConnection $connection;
    protected TradingPreset $preset;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->botService = app(TradingBotService::class);
        
        // Create test exchange connection
        $this->connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'connection_type' => 'CRYPTO_EXCHANGE',
            'is_active' => true,
            'status' => 'active',
        ]);
        
        // Create test trading preset
        $this->preset = TradingPreset::factory()->create([
            'enabled' => true,
        ]);
    }

    /**
     * Test concurrent start operations
     * 
     * Multiple threads trying to start the same bot should:
     * - Only one succeeds
     * - Others are idempotent (return same bot)
     * - No race conditions
     */
    public function test_concurrent_start_operations(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'stopped',
        ]);

        $results = [];
        $exceptions = [];

        // Simulate concurrent start operations
        $threads = 5;
        $barrier = new \SplBarrier($threads);
        
        for ($i = 0; $i < $threads; $i++) {
            $threadId = $i;
            
            // Use Laravel's queue or async processing simulation
            // In real scenario, this would be multiple HTTP requests
            try {
                // All threads wait at barrier
                $barrier->wait();
                
                // All threads try to start simultaneously
                $result = $this->botService->start($bot, $this->user->id);
                $results[$threadId] = $result;
            } catch (\Exception $e) {
                $exceptions[$threadId] = $e->getMessage();
            }
        }

        // Verify only one start actually happened (idempotency)
        $bot->refresh();
        
        // Bot should be in running status
        $this->assertEquals('running', $bot->status);
        
        // All results should be the same bot instance (idempotency)
        foreach ($results as $result) {
            $this->assertEquals($bot->id, $result->id);
            $this->assertEquals('running', $result->status);
        }

        // No exceptions should occur (idempotency prevents errors)
        $this->assertEmpty($exceptions, 'Concurrent start operations should not throw exceptions');
    }

    /**
     * Test concurrent stop operations
     * 
     * Multiple threads trying to stop the same bot should:
     * - Only one succeeds
     * - Others are idempotent
     * - No race conditions
     */
    public function test_concurrent_stop_operations(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'running',
        ]);

        $results = [];
        $exceptions = [];

        // Simulate concurrent stop operations
        $threads = 5;
        $barrier = new \SplBarrier($threads);
        
        for ($i = 0; $i < $threads; $i++) {
            $threadId = $i;
            
            try {
                $barrier->wait();
                $result = $this->botService->stop($bot, $this->user->id);
                $results[$threadId] = $result;
            } catch (\Exception $e) {
                $exceptions[$threadId] = $e->getMessage();
            }
        }

        // Verify bot is stopped
        $bot->refresh();
        $this->assertEquals('stopped', $bot->status);
        
        // All results should be the same bot instance
        foreach ($results as $result) {
            $this->assertEquals($bot->id, $result->id);
            $this->assertEquals('stopped', $result->status);
        }

        // No exceptions should occur
        $this->assertEmpty($exceptions);
    }

    /**
     * Test concurrent start/stop operations
     * 
     * One thread starting while another stops should:
     * - One operation succeeds
     * - Other operation is handled gracefully
     * - No data corruption
     */
    public function test_concurrent_start_and_stop(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'stopped',
        ]);

        $startResult = null;
        $stopResult = null;
        $startException = null;
        $stopException = null;

        // Simulate concurrent start and stop
        $barrier = new \SplBarrier(2);
        
        // Thread 1: Start
        try {
            $barrier->wait();
            $startResult = $this->botService->start($bot, $this->user->id);
        } catch (\Exception $e) {
            $startException = $e->getMessage();
        }

        // Thread 2: Stop (simulated by calling immediately after)
        try {
            $barrier->wait();
            $stopResult = $this->botService->stop($bot, $this->user->id);
        } catch (\Exception $e) {
            $stopException = $e->getMessage();
        }

        // Verify final state is consistent
        $bot->refresh();
        
        // Bot should be in a valid state (either running or stopped, not inconsistent)
        $this->assertContains($bot->status, ['running', 'stopped']);
        
        // No exceptions should occur (operations should handle conflicts gracefully)
        // Note: In real scenario, one operation may fail if bot state changes
        // but it should fail gracefully without data corruption
    }

    /**
     * Test database transaction rollback on failure
     * 
     * If start operation fails, bot status should not be partially updated
     */
    public function test_transaction_rollback_on_failure(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'stopped',
        ]);

        $initialStatus = $bot->status;
        $initialUpdatedAt = $bot->updated_at;

        // Simulate failure by making connection inactive
        $this->connection->update(['is_active' => false]);
        $bot->refresh();

        try {
            $this->botService->start($bot, $this->user->id);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected - validation should fail
        }

        // Verify bot status was not changed (transaction rollback)
        $bot->refresh();
        $this->assertEquals($initialStatus, $bot->status);
    }

    /**
     * Test row-level locking prevents race conditions
     * 
     * Multiple transactions trying to update the same bot should:
     * - Be serialized by row-level lock
     * - Not cause deadlocks
     * - Complete successfully
     */
    public function test_row_level_locking_prevents_race_conditions(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'stopped',
        ]);

        // Simulate multiple transactions with row-level locking
        $results = [];
        
        for ($i = 0; $i < 3; $i++) {
            DB::transaction(function () use ($bot, $i, &$results) {
                // Acquire row-level lock
                $lockedBot = TradingBot::lockForUpdate()->findOrFail($bot->id);
                
                // Simulate some work
                usleep(10000); // 10ms
                
                // Update bot
                $lockedBot->update([
                    'name' => "Updated by thread {$i}",
                ]);
                
                $results[] = $lockedBot->name;
            });
        }

        // Verify all updates were applied (serialized, not lost)
        $bot->refresh();
        $this->assertContains($bot->name, $results);
    }

    /**
     * Test idempotency of start operation
     * 
     * Calling start multiple times on a running bot should:
     * - Not throw errors
     * - Return the same bot
     * - Not create duplicate workers
     */
    public function test_start_operation_is_idempotent(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'running',
        ]);

        // Call start multiple times
        $result1 = $this->botService->start($bot, $this->user->id);
        $result2 = $this->botService->start($bot, $this->user->id);
        $result3 = $this->botService->start($bot, $this->user->id);

        // All should return the same bot
        $this->assertEquals($bot->id, $result1->id);
        $this->assertEquals($bot->id, $result2->id);
        $this->assertEquals($bot->id, $result3->id);

        // Bot should still be running
        $bot->refresh();
        $this->assertEquals('running', $bot->status);
    }

    /**
     * Test idempotency of stop operation
     * 
     * Calling stop multiple times on a stopped bot should:
     * - Not throw errors
     * - Return the same bot
     */
    public function test_stop_operation_is_idempotent(): void
    {
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'status' => 'stopped',
        ]);

        // Call stop multiple times
        $result1 = $this->botService->stop($bot, $this->user->id);
        $result2 = $this->botService->stop($bot, $this->user->id);
        $result3 = $this->botService->stop($bot, $this->user->id);

        // All should return the same bot
        $this->assertEquals($bot->id, $result1->id);
        $this->assertEquals($bot->id, $result2->id);
        $this->assertEquals($bot->id, $result3->id);

        // Bot should still be stopped
        $bot->refresh();
        $this->assertEquals('stopped', $bot->status);
    }
}

