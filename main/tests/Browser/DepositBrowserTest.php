<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;

class DepositBrowserTest extends DuskTestCase
{
    public function test_authenticated_user_can_make_deposit()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'email_verified_at' => now(),
                'status' => 1,
            ]);

            $browser->loginAs($user->id)
                ->visit('/deposit')
                ->type('amount', '100')
                ->press('.sp_theme_btn')
                ->pause(500)
                ->assertSee('Deposit request submitted');
        });
    }
}
