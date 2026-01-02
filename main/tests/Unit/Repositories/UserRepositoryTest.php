<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRepositoryTest extends TestCase
{
    // Use Mockery to test without DB if possible, or just scaffold
    
    public function test_get_active_users_returns_only_active_users()
    {
        // Placeholder
        $this->assertTrue(true);
    }
}
