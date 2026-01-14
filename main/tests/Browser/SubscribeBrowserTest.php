<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Plan;

class SubscribeBrowserTest extends DuskTestCase
{
    public function test_authenticated_user_can_subscribe_to_plan()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
                'status' => 1,
            ]);

            $browser->loginAs($user->id)
                ->visit('/plans')
                ->clickLink($this->faker->randomElement([1, 2, 3]))
                ->pause(500)
                ->assertPathIs('/api/user/plans/subscribe')
                ->assertSee('Subscribe to Plan');
        });
    }

    public function test_user_cannot_subscribe_without_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/plans')
                ->clickLink($this->faker->randomElement([1, 2, 3]))
                ->pause(500)
                ->assertPathIs('/login')
                ->assertSee('You need to login to subscribe');
        });
    }
}
