<?php

namespace Tests\Feature;

use App\Models\Month;
use App\Models\RoadmapDay;
use App\Models\User;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadmapPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_the_roadmap_lists_the_months_with_days_done_and_gate_results(): void
    {
        // 01/10 is day 1, so 29/10 is day 29: week 5, month 2.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();
        $other = User::factory()->create();
        $dayIds = RoadmapDay::whereIn('number', [1, 2, 3])->pluck('id', 'number');
        $user->dayCompletions()->create(['roadmap_day_id' => $dayIds[1], 'minutes' => 60, 'completed_at' => '2026-10-01 12:00:00']);
        $user->dayCompletions()->create(['roadmap_day_id' => $dayIds[2], 'minutes' => 60, 'completed_at' => '2026-10-02 12:00:00']);
        $other->dayCompletions()->create(['roadmap_day_id' => $dayIds[3], 'minutes' => 60, 'completed_at' => '2026-10-03 12:00:00']);
        $user->gateResults()->create(['month_id' => Month::where('number', 1)->value('id'), 'passed' => true, 'checked_at' => '2026-10-28 12:00:00']);

        $response = $this->actingAs($user)->get('/roadmap');

        $response->assertOk();
        $response->assertSee('Hôm nay là ngày 29/728.');
        $response->assertSee(route('roadmap.weeks.show', 5));
        $response->assertSeeInOrder(['Giai đoạn 1:', 'Tháng 1', '2/28 ngày', 'Cổng: ✓ Đạt', 'Tháng 2', 'đang học', '0/28 ngày', 'Cổng: chưa kiểm tra']);
        $response->assertSeeInOrder(['Giai đoạn 6:', 'Tháng 24']);
    }

    public function test_a_month_shows_its_weeks_and_an_open_gate_form(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();
        $user->dayCompletions()->create(['roadmap_day_id' => RoadmapDay::where('number', 22)->value('id'), 'minutes' => 60, 'completed_at' => '2026-10-22 12:00:00']);

        $response = $this->actingAs($user)->get('/roadmap/months/1');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Tháng 1 · tuần 1–4',
            'Tuần 1: Chào hỏi và động từ be', '0/7 ngày',
            'Tuần 4: Thói quen buổi sáng', '1/7 ngày',
            'Cổng kiểm tra cuối tháng', 'Đọc đúng 20 nguyên âm',
        ]);
        $response->assertSee(route('roadmap.weeks.show', 4));
        $response->assertSee('Lưu kết quả');
        $response->assertDontSee('Xóa kết quả');
    }

    public function test_a_month_shows_the_saved_gate_result_in_the_learners_timezone(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();
        $user->gateResults()->create([
            'month_id' => Month::where('number', 1)->value('id'),
            'passed' => false,
            'note' => 'Còn nhầm /ɪ/ và /iː/',
            'checked_at' => '2026-10-28 18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/roadmap/months/1');

        // 18:00 UTC is 01:00 the next day in Vietnam.
        $response->assertSee('✗ Chưa đạt');
        $response->assertSee('ghi lúc 01:00 29/10/2026');
        $response->assertSee('Còn nhầm /ɪ/ và /iː/');
        $response->assertSee('Xóa kết quả');
    }

    public function test_the_gate_form_stays_hidden_before_the_months_last_week(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();
        $month = Month::where('number', 2)->firstOrFail();

        $response = $this->actingAs($user)->get('/roadmap/months/2');

        $response->assertOk();
        $response->assertDontSee('Lưu kết quả');
        $response->assertSee('Nhập được kết quả từ ngày '.$user->roadmapDayDate($month->gateOpensOnDay())?->format('d/m/Y').' (ngày đầu của tuần 8).');
    }

    public function test_a_week_lists_its_days_and_marks_made_up_days(): void
    {
        // 25/10 is day 1, so 29/10 is day 5 of week 1.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-25'])->refresh();
        $dayIds = RoadmapDay::whereIn('number', [1, 2])->pluck('id', 'number');
        // Day 1 (25/10) ticked on 27/10, day 2 (26/10) ticked on time.
        $user->dayCompletions()->create(['roadmap_day_id' => $dayIds[1], 'minutes' => 30, 'completed_at' => '2026-10-27 03:00:00']);
        $user->dayCompletions()->create(['roadmap_day_id' => $dayIds[2], 'minutes' => 45, 'completed_at' => '2026-10-26 10:00:00']);

        $response = $this->actingAs($user)->get('/roadmap/weeks/1');

        $response->assertOk();
        $response->assertSee('Tuần 1: Chào hỏi và động từ be');
        $response->assertSeeInOrder([
            'N1 · Ngữ pháp – học', '25/10/2026', '✓ Đã xong lúc 10:00 27/10/2026 · 30 phút · học bù', 'Lưu số phút',
            'N2 · Ngữ pháp – luyện', '✓ Đã xong lúc 17:00 26/10/2026 · 45 phút', 'Lưu số phút',
            'N3 · Nói', 'Đánh dấu học bù',
            'N5 · Đầu ra tuần', 'hôm nay', 'Đánh dấu đã xong',
            'N6 · Kiểm tra', 'Chưa tới ngày này.',
            'N7 · Nghỉ', 'Chưa tới ngày này.',
        ]);
        $response->assertDontSee('45 phút · học bù');
        $response->assertDontSee('nhập kết quả cổng kiểm tra');
    }

    public function test_the_last_week_of_a_month_links_to_the_gate(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();

        $response = $this->actingAs($user)->get('/roadmap/weeks/4');

        $response->assertSee('nhập kết quả cổng kiểm tra');
        $response->assertSee(route('roadmap.months.show', 1).'#gate');
    }

    public function test_only_the_form_that_was_sent_shows_its_old_input_on_the_week_page(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-25'])->refresh();

        $response = $this->actingAs($user)->from('/roadmap/weeks/1')->followingRedirects()
            ->put('/days/2/completion', ['minutes' => 999, 'day' => 2]);

        $response->assertOk();
        $this->assertSame(1, substr_count((string) $response->getContent(), 'value="999"'));
        $response->assertSeeInOrder(['id="minutes-2"', 'value="999"', 'id="minutes-3"'], false);
    }

    public function test_without_a_start_date_no_day_or_gate_can_be_ticked(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->get('/roadmap')->assertSee('Bạn chưa chọn ngày bắt đầu lộ trình.');
        $this->actingAs($user)->get('/roadmap/weeks/1')->assertSee('Chưa tới ngày này.')->assertDontSee('Đánh dấu');
        $this->actingAs($user)->get('/roadmap/months/1')->assertSee('Chọn ngày bắt đầu lộ trình trong Cài đặt học')->assertDontSee('Lưu kết quả');
    }

    public function test_unknown_months_and_weeks_are_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/roadmap/months/25')->assertNotFound();
        $this->actingAs($user)->get('/roadmap/weeks/105')->assertNotFound();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/roadmap')->assertRedirect('/login');
        $this->get('/roadmap/months/1')->assertRedirect('/login');
        $this->get('/roadmap/weeks/1')->assertRedirect('/login');
    }
}
