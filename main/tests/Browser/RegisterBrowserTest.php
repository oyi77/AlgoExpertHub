<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;

class RegisterBrowserTest extends DuskTestCase
{
    public function test_user_can_register_with_valid_data()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->type('username', $this->faker->userName())
                ->type('phone', $this->faker->phoneNumber())
                ->type('email', $this->faker->email())
                ->type('password', 'Password123!@#')
                ->type('password_confirmation', 'Password123!@#')
                ->check('terms')
                ->press('.sp_theme_btn')
                ->assertPathIs('/dashboard')
                ->assertSeeInDatabase('users', [
                    'email' => $browser->attribute('email'),
                ]);
        });
    }

    public function test_user_cannot_register_without_accepting_terms()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->type('username', $this->faker->userName())
                ->type('phone', $this->faker->phoneNumber())
                ->type('email', $this->faker->email())
                ->type('password', 'Password123!@#')
                ->type('password_confirmation', 'Password123!@#')
                ->press('.sp_theme_btn')
                ->assertSee('You must accept Terms of Service and Privacy Policy')
                ->assertPathIs('/register');
        });
    }

    public function test_user_cannot_register_with_duplicate_email()
    {
        $existingEmail = User::first()->email;
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->type('username', $this->faker->userName())
                ->type('phone', $this->faker->phoneNumber())
                ->type('email', $existingEmail)
                ->type('password', 'Password123!@#')
                ->type('password_confirmation', 'Password123!@#')
                ->check('terms')
                ->press('.sp_theme_btn')
                ->assertSee('The email has already been taken.');
        });
    }

    private function faker()
    {
        return $this->app->make(\Faker\Factory::class);
    }
}
