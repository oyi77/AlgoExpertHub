<?php

namespace Addons\AiConnectionAddon\Tests\Feature;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\MultiChannelSignalAddon\App\Models\AiParsingProfile;
use Addons\MultiChannelSignalAddon\App\Parsers\AiMessageParser;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class MultiChannelParsingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_parses_message_using_centralized_connection()
    {
        // Create provider and connection
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test OpenAI',
            'credentials' => ['api_key' => 'test-key'],
            'settings' => ['model' => 'gpt-3.5-turbo', 'temperature' => 0.3],
            'status' => 'active',
            'priority' => 1,
        ]);

        // Create parsing profile
        $profile = AiParsingProfile::create([
            'ai_connection_id' => $connection->id,
            'name' => 'Test Profile',
            'parsing_prompt' => null,
            'priority' => 50,
            'enabled' => true,
        ]);

        // Mock AI response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'currency_pair' => 'EUR/USD',
                        'direction' => 'buy',
                        'open_price' => 1.0950,
                        'sl' => 1.0900,
                        'tp' => 1.1000,
                        'timeframe' => '1H',
                    ])]],
                ],
                'usage' => ['total_tokens' => 150],
            ], 200),
        ]);

        // Create parser with profile
        $parser = new AiMessageParser($profile);

        // Parse test message
        $message = "BUY EUR/USD at 1.0950, SL: 1.0900, TP: 1.1000, Timeframe: 1H";
        $result = $parser->parse($message);

        // Verify parsing succeeded
        $this->assertNotNull($result);
        $this->assertEquals('EUR/USD', $result->currencyPair);
        $this->assertEquals('buy', $result->direction);

        // Verify usage was tracked
        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $connection->id,
            'feature' => 'signal_parsing',
            'success' => true,
        ]);

        // Verify connection success recorded
        $connection->refresh();
        $this->assertEquals(1, $connection->success_count);
    }

    /** @test */
    public function it_handles_parsing_failure_gracefully()
    {
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test OpenAI',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
        ]);

        $profile = AiParsingProfile::create([
            'ai_connection_id' => $connection->id,
            'name' => 'Test Profile',
            'enabled' => true,
        ]);

        // Mock API error
        Http::fake([
            'api.openai.com/*' => Http::response('API Error', 500),
        ]);

        $parser = new AiMessageParser($profile);
        $result = $parser->parse("BUY EUR/USD");

        // Should return null on failure
        $this->assertNull($result);

        // Verify error was tracked
        $connection->refresh();
        $this->assertEquals(1, $connection->error_count);
        
        $this->assertDatabaseHas('ai_connection_usage', [
            'connection_id' => $connection->id,
            'feature' => 'signal_parsing',
            'success' => false,
        ]);
    }

    /** @test */
    public function it_uses_custom_parsing_prompt_from_profile()
    {
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'status' => 'active',
        ]);

        $connection = AiConnection::create([
            'provider_id' => $provider->id,
            'name' => 'Test',
            'credentials' => ['api_key' => 'test-key'],
            'status' => 'active',
        ]);

        $customPrompt = 'Custom parsing instructions: {message}. Extract signals.';

        $profile = AiParsingProfile::create([
            'ai_connection_id' => $connection->id,
            'name' => 'Custom Prompt Profile',
            'parsing_prompt' => $customPrompt,
            'enabled' => true,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'currency_pair' => 'BTC/USD',
                        'direction' => 'sell',
                    ])]],
                ],
                'usage' => ['total_tokens' => 50],
            ], 200),
        ]);

        $parser = new AiMessageParser($profile);
        
        // Verify parser uses the profile
        $this->assertEquals($profile->id, $parser->profile->id);
    }
}

