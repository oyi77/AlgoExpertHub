<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Signal;
use App\Models\Plan;
use App\Services\SignalService;
use App\Services\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class SignalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SignalService $signalService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $cacheManager = $this->createMock(CacheManager::class);
        $this->signalService = new SignalService($cacheManager);
    }

    public function test_all_signals_retrieval_without_plan()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $result = $this->signalService->allSignals([]);

        $this->assertEquals('success', $result['type']);
        $this->assertEmpty($result['data']['signals']->items());
    }

    public function test_signal_details_retrieval()
    {
        $signal = Signal::factory()->create();

        $result = $this->signalService->details($signal->id);

        $this->assertEquals('success', $result['type']);
        $this->assertEquals($signal->id, $result['data']['signal']->id);
    }

    public function test_signal_details_not_found()
    {
        $result = $this->signalService->details(99999);

        $this->assertEquals('error', $result['type']);
        $this->assertEquals(404, $result['code']);
    }
}
