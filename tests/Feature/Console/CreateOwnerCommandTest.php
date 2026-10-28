<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    private const OPTIONS = [
        '--name' => 'Tuấn',
        '--email' => 'owner@example.com',
        '--password' => 'secret-password',
    ];

    public function test_it_creates_a_verified_owner_account(): void
    {
        $this->artisan('app:create-owner', self::OPTIONS)->assertSuccessful();

        $user = User::sole();
        $this->assertSame('Tuấn', $user->name);
        $this->assertSame('owner@example.com', $user->email);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_the_owner_can_log_in_after_creation(): void
    {
        $this->artisan('app:create-owner', self::OPTIONS)->assertSuccessful();

        $this->post('/login', [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_it_rejects_an_email_that_is_already_taken(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('app:create-owner', self::OPTIONS)->assertFailed();

        $this->assertSame(1, User::count());
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('app:create-owner', ['--password' => 'short'] + self::OPTIONS)->assertFailed();

        $this->assertSame(0, User::count());
    }
}
