<?php

namespace Tests\Feature;

use App\Models\Month;
use App\Models\RoadmapDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_it_shows_completion_time_and_gates_passed(): void
    {
        // 27/10 is day 1, so 29/10 is day 3.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();
        $this->tick($user, 1, '2026-10-27 03:00:00', 120);
        $this->tick($user, 2, '2026-10-28 03:00:00', 90);
        $this->tick($user, 3, '2026-10-28 18:00:00', 50);
        $monthIds = Month::pluck('id', 'number');
        $user->gateResults()->create(['month_id' => $monthIds[1], 'passed' => true, 'checked_at' => now()]);
        $user->gateResults()->create(['month_id' => $monthIds[2], 'passed' => false, 'checked_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSeeInOrder(['Hoàn thành lộ trình', '0,4%', '3/728 ngày']);
        $response->assertSeeInOrder(['Tổng thời gian học', '4 giờ 20 phút', 'Trung bình 87 phút/ngày đã xong']);
        $response->assertSeeInOrder(['Cổng tháng đã đạt', '1/24']);
        $response->assertSeeInOrder(['Thời gian học theo tháng', 'Tháng 1', 'đang học', 'width: 100%', '4 giờ 20 phút · 3 ngày']);
    }

    public function test_the_streak_follows_the_learners_clock(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();
        // In Vietnam these are 27/10 10:00, 28/10 01:00 and 29/10 01:15: three
        // days in a row. In UTC they fall on 27/10, 27/10 and 28/10.
        $this->tick($user, 1, '2026-10-27 03:00:00');
        $this->tick($user, 2, '2026-10-27 18:00:00');
        $this->tick($user, 3, '2026-10-28 18:15:00');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSeeInOrder(['Chuỗi ngày học', '3 ngày', 'Dài nhất: 3 ngày']);
    }

    public function test_rest_days_and_today_do_not_break_the_streak_but_a_missed_day_does(): void
    {
        // 13/10 is day 1, so the rest days are 19/10 (day 7) and 26/10 (day 14),
        // and 29/10 is day 17.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-13'])->refresh();
        // 13–18, rest day 19 skipped, 20–21: 8 days. 22/10 (day 10) missed.
        // 23–25, rest day 26 skipped, 27–28: 5 days. Nothing yet on 29/10.
        foreach ([1, 2, 3, 4, 5, 6, 8, 9, 11, 12, 13, 15, 16] as $number) {
            $date = CarbonImmutable::parse('2026-10-13')->addDays($number - 1)->setTime(3, 0);
            $this->tick($user, $number, $date->toDateTimeString());
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSeeInOrder(['Chuỗi ngày học', '5 ngày', 'Dài nhất: 8 ngày']);

        // Making up day 10 today counts for today, not for 22/10.
        $this->tick($user, 10, now()->toDateTimeString());

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSeeInOrder(['Chuỗi ngày học', '6 ngày', 'Dài nhất: 8 ngày']);
    }

    public function test_minutes_go_to_the_roadmap_month_of_the_day_and_later_months_are_hidden(): void
    {
        // 30/09 is day 1, so 29/10 is day 30, in month 2 (days 29–56).
        $user = User::factory()->create(['roadmap_start_date' => '2026-09-30'])->refresh();
        $this->tick($user, 1, '2026-09-30 03:00:00', 60);
        // Day 28 made up on the date of day 29 still belongs to month 1.
        $this->tick($user, 28, '2026-10-28 03:00:00', 30);
        $this->tick($user, 29, '2026-10-28 04:00:00', 120);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSeeInOrder([
            'Thời gian học theo tháng',
            'Tháng 1', 'width: 75%', '1 giờ 30 phút · 2 ngày',
            'Tháng 2', 'đang học', 'width: 100%', '2 giờ · 1 ngày',
        ]);
        $response->assertDontSee(route('roadmap.months.show', 3));
    }

    public function test_on_the_first_day_it_shows_zeros(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-29'])->refresh();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSeeInOrder([
            '0,0%', '0/728 ngày',
            'Chuỗi ngày học', '0 ngày', 'Dài nhất: 0 ngày',
            'Tổng thời gian học', '0 phút', 'Trung bình 0 phút/ngày đã xong',
            'Cổng tháng đã đạt', '0/24',
            'Tháng 1', 'width: 0%', '0 phút · 0 ngày',
        ]);
    }

    public function test_it_shows_no_figures_before_the_roadmap_starts(): void
    {
        $withoutStart = User::factory()->create()->refresh();
        $notYet = User::factory()->create(['roadmap_start_date' => '2026-11-01'])->refresh();

        foreach ([$withoutStart, $notYet] as $user) {
            $response = $this->actingAs($user)->get('/dashboard');

            $response->assertOk();
            $response->assertDontSee('Chuỗi ngày học');
            $response->assertDontSee('Thời gian học theo tháng');
        }
    }

    private function tick(User $user, int $number, string $completedAt, int $minutes = 60): void
    {
        $user->dayCompletions()->create([
            'roadmap_day_id' => RoadmapDay::where('number', $number)->value('id'),
            'minutes' => $minutes,
            'completed_at' => $completedAt,
        ]);
    }
}
