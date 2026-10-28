<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_vietnam_time(): void
    {
        $user = User::factory()->create()->refresh();

        $this->assertSame('Asia/Ho_Chi_Minh', $user->timezone);
    }

    public function test_local_now_follows_the_users_timezone(): void
    {
        $user = User::factory()->create()->refresh();

        // 18:30 UTC is already 01:30 the next day in Vietnam (UTC+7).
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));

        $this->assertSame('2026-10-29 01:30', $user->localNow()->format('Y-m-d H:i'));
    }
}
