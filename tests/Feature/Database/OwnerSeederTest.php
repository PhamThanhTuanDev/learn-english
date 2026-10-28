<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\OwnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_owner_from_config(): void
    {
        $this->setOwner('owner@example.com', 'secret-password');

        $this->seed(OwnerSeeder::class);

        $user = User::sole();
        $this->assertSame('owner@example.com', $user->email);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_running_it_again_keeps_the_existing_account(): void
    {
        $this->setOwner('owner@example.com', 'secret-password');
        $this->seed(OwnerSeeder::class);

        $this->setOwner('owner@example.com', 'changed-password');
        $this->seed(OwnerSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('secret-password', User::sole()->password));
    }

    public function test_it_does_nothing_without_owner_credentials(): void
    {
        $this->setOwner('', '');

        $this->seed(OwnerSeeder::class);

        $this->assertSame(0, User::count());
    }

    private function setOwner(string $email, string $password): void
    {
        config(['app.owner' => ['name' => 'Tuấn', 'email' => $email, 'password' => $password]]);
    }
}
