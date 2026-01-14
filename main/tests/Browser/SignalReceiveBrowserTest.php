<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Signal;

class SignalReceiveBrowserTest extends DuskTestCase
{
    public function test_authenticated_user_can_view_published_signals()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
                'status' => 1,
            ]);

            Signal::factory()->create([
                'status' => 1,
                'pair' => 'EUR/USD',
            ]);

            $browser->loginAs($user->id)
                ->visit('/signals')
                ->pause(1000)
                ->assertSee('Signals')
                ->assertSee('EUR/USD');
        });
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/signals')
                ->pause(500)
                ->assertPathIs('/login')
                ->assertSee('You need to login to view signals');
        });
    }
}
