<?php

namespace Tests\Feature;

use App\Models\RoadmapDay;
use App\Models\RoadmapResource;
use App\Models\User;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_it_shows_todays_task_with_the_stage_blocks_and_the_week(): void
    {
        // 27/10 is day 1, so 29/10 is day 3 of week 1.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();
        $names = RoadmapResource::pluck('name', 'code');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Tuần 1 · Ngày 3: Chào hỏi và động từ be');
        $response->assertSee('Ngày thứ 3/728 · Tháng 1');
        $response->assertSee('Việc của hôm nay: Nói');
        $response->assertSee('Đánh dấu đã xong');
        $response->assertSeeInOrder(['Các khối cố định mỗi ngày', 'tổng 120 phút', 'Phát âm (IPA)', 'Sổ lỗi']);
        $response->assertSeeInOrder(['Tuần 1: Chào hỏi và động từ be', 'Trọng tâm', 'Nghe (VOA)']);
        // Resources cited on the page, sorted by code; others are left out.
        $response->assertSeeInOrder(['Tài liệu nhắc tới hôm nay', $names['R6'], $names['R9'], $names['R11']]);
        $response->assertDontSee($names['R27']);
    }

    public function test_it_shows_which_days_of_the_week_are_done(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();
        $dayIds = RoadmapDay::whereIn('number', [1, 3])->pluck('id', 'number');
        $user->dayCompletions()->create(['roadmap_day_id' => $dayIds[1], 'minutes' => 120, 'completed_at' => '2026-10-27 12:00:00']);
        $user->dayCompletions()->create(['roadmap_day_id' => $dayIds[3], 'minutes' => 50, 'completed_at' => '2026-10-28 18:00:00']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Ngữ pháp – học – đã xong');
        $response->assertDontSee('Ngữ pháp – luyện – đã xong');
        // 18:00 UTC is 01:00 the next day in Vietnam.
        $response->assertSee('Đã xong lúc 01:00 29/10/2026 · 50 phút');
        $response->assertSee('Lưu số phút');
        $response->assertSee('Bỏ đánh dấu đã xong');
    }

    public function test_without_a_start_date_it_links_to_the_settings(): void
    {
        $user = User::factory()->create()->refresh();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Bạn chưa chọn ngày bắt đầu lộ trình.');
        $response->assertSee(route('settings.edit'));
        $response->assertDontSee('Việc của hôm nay');
    }

    public function test_after_the_last_day_it_says_the_roadmap_is_finished(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2024-10-01'])->refresh();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Bạn đã đi hết 104 tuần của lộ trình.');
        $response->assertDontSee('Việc của hôm nay');
    }
}
