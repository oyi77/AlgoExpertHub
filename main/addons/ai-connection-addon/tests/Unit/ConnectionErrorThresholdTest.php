<?php

namespace Addons\AiConnectionAddon\Tests\Unit;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConnectionErrorThresholdTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_marks_connection_as_error_at_exactly_10_errors()
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 9, // Start at 9 (one below threshold)
        ]);

        // Record one more error (should reach threshold of 10)
        $connection->recordError('Test error');

        // Refresh to get latest from database
        $connection->refresh();

        // CRITICAL TEST: Should now be marked as 'error' status
        $this->assertEquals(10, $connection->error_count, 'Error count should be exactly 10');
        $this->assertEquals('error', $connection->status, 'Status should be "error" at 10 errors');
    }

    /** @test */
    public function it_stays_active_below_error_threshold()
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 8, // Below threshold
        ]);

        // Record error (9 total, still below 10)
        $connection->recordError('Test error');
        $connection->refresh();

        $this->assertEquals(9, $connection->error_count);
        $this->assertEquals('active', $connection->status, 'Should still be active at 9 errors');
    }

    /** @test */
    public function it_correctly_increments_through_threshold()
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 0,
        ]);

        // Record errors up to threshold
        for ($i = 1; $i < 10; $i++) {
            $connection->recordError("Error {$i}");
            $connection->refresh();
            
            $this->assertEquals($i, $connection->error_count, "Error count should be {$i}");
            $this->assertEquals('active', $connection->status, "Should be active at {$i} errors");
        }

        // 10th error should trigger status change
        $connection->recordError('Error 10');
        $connection->refresh();

        $this->assertEquals(10, $connection->error_count, 'Should be at 10 errors');
        $this->assertEquals('error', $connection->status, 'Should be "error" status at 10 errors');

        // 11th error should maintain error status
        $connection->recordError('Error 11');
        $connection->refresh();

        $this->assertEquals(11, $connection->error_count);
        $this->assertEquals('error', $connection->status, 'Should remain "error" status');
    }

    /** @test */
    public function error_count_in_log_shows_correct_value()
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 9,
        ]);

        // Mock Log to capture what's being logged
        \Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                // Verify error_count in log is the NEW value (10), not stale value (9)
                return $context['error_count'] === 10;
            });

        $connection->recordError('Test error message');
    }

    /** @test */
    public function healthy_scope_excludes_connections_at_threshold()
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        // Create connections with various error counts
        $healthy = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Healthy',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 5, // Below default threshold of 10
        ]);

        $atThreshold = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'At Threshold',
            'credentials' => ['api_key' => 'test'],
            'status' => 'active',
            'error_count' => 10, // At threshold
        ]);

        $aboveThreshold = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Above Threshold',
            'credentials' => ['api_key' => 'test'],
            'status' => 'error',
            'error_count' => 15, // Above threshold
        ]);

        // healthy() scope with default threshold of 10 should exclude >= 10
        $healthyConnections = AiConnection::healthy(10)->get();

        $this->assertCount(1, $healthyConnections, 'Should only return connections with error_count < 10');
        $this->assertEquals($healthy->id, $healthyConnections->first()->id);
    }

    /** @test */
    public function recordSuccess_resets_error_count_and_maintains_active_status()
    {
        $provider = AiProvider::create([
            'name' => 'Test Provider',
            'slug' => 'test',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test Connection',
            'credentials' => ['api_key' => 'test'],
            'status' => 'error', // Was in error state
            'error_count' => 12, // Had many errors
        ]);

        $connection->recordSuccess();
        $connection->refresh();

        $this->assertEquals(0, $connection->error_count, 'Error count should be reset to 0');
        $this->assertEquals('active', $connection->status, 'Status should be reset to active');
        $this->assertNotNull($connection->last_used_at);
        $this->assertEquals(1, $connection->success_count);
    }
}

