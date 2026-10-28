<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadmapDayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_there_is_no_roadmap_day_without_a_start_date(): void
    {
        $user = User::factory()->create()->refresh();

        $this->assertNull($user->roadmapDayNumber());
    }

    public function test_the_start_date_is_day_one_in_the_users_timezone(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-29'])->refresh();

        $this->assertSame(1, $user->roadmapDayNumber());
    }

    public function test_a_future_start_date_has_no_roadmap_day_yet(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-11-02'])->refresh();

        $this->assertNull($user->roadmapDayNumber());
    }

    public function test_the_dashboard_shows_the_current_week_and_day(): void
    {
        // Day 11 of the roadmap: week 2, day 4.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-19'])->refresh();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Tuần 2 · Ngày 4');
        $response->assertSee('Ngày thứ 11/728');
    }

    public function test_the_dashboard_asks_for_a_start_date_when_missing(): void
    {
        $user = User::factory()->create()->refresh();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Bạn chưa chọn ngày bắt đầu lộ trình.');
    }
}
