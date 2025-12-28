<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class LandingSwitcherTest extends TestCase
{
    /**
     * Test that the Bot Sales landing page renders with expected elements.
     */
    public function test_bot_sales_landing_page_renders_correctly()
    {
        // Ensure the landing_page configuration is set to 'bot-sales'
        DB::table('configurations')->update(['landing_page' => 'bot-sales']);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('frontend.landings.bot-sales.index');
        // Check for key CSS classes indicating redesign
        $response->assertSee('glass-card');
        $response->assertSee('data-aos="fade-up"');
        $response->assertSee('shimmer');
        $response->assertSee('ticker-section');
    }
}
