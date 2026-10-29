<?php

namespace Tests\Feature;

use App\Actions\AddNewCards;
use App\Models\Card;
use App\Models\User;
use App\Models\Word;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AddNewCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_a_stage_1_day_adds_8_a1_words_due_today_only_once(): void
    {
        Word::factory()->count(5)->level('A2')->create();
        $a1 = Word::factory()->count(10)->create();
        $user = $this->userOnDay(1);

        $this->assertSame(8, app(AddNewCards::class)->handle($user));
        $this->assertSame(0, app(AddNewCards::class)->handle($user));

        $cards = $user->cards()->orderBy('id')->get();
        $this->assertSame($a1->take(8)->modelKeys(), $cards->pluck('word_id')->all());
        $this->assertSame(['2026-10-29'], $cards->map(fn (Card $card) => $card->due_on->toDateString())->unique()->values()->all());
        $this->assertSame(2.5, $cards[0]->ease_factor);
        $this->assertSame(0, $cards[0]->repetitions);
        $this->assertNull($cards[0]->last_reviewed_at);
    }

    public function test_the_quota_counts_the_learners_own_day_and_missed_days_are_not_made_up(): void
    {
        Word::factory()->count(40)->create();

        // Still 23:30 on 28/10 in Vietnam: day 1.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 16, 30));
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-28'])->refresh();
        $this->assertSame(8, app(AddNewCards::class)->handle($user));

        // The same UTC date, but 29/10 in Vietnam: day 2 gets its own 8.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
        $this->assertSame(8, app(AddNewCards::class)->handle($user));

        // Days 3 and 4 were missed: day 5 still adds only 8.
        $this->travelTo(now('UTC')->setDateTime(2026, 11, 1, 3, 0));
        $this->assertSame(8, app(AddNewCards::class)->handle($user));

        $this->assertSame(24, $user->cards()->distinct()->count('word_id'));
    }

    public function test_lower_then_higher_levels_follow_when_the_stage_runs_out_and_unlevelled_words_come_last(): void
    {
        // Created out of level order, so the id alone cannot give the result.
        foreach ([[null, 2], ['C2', 2], ['C1', 3], ['B2', 3], ['A2', 1], ['A1', 1], ['B1', 2]] as [$level, $count]) {
            Word::factory()->count($count)->level($level)->create();
        }

        // Day 183 opens week 27, the first week of stage 3 (B1, 12 words).
        $user = $this->userOnDay(183);
        $this->assertSame(12, app(AddNewCards::class)->handle($user));
        $this->assertSame(
            ['B1', 'B1', 'A1', 'A2', 'B2', 'B2', 'B2', 'C1', 'C1', 'C1', 'C2', 'C2'],
            $user->cards()->with('word')->orderBy('id')->get()->pluck('word.level')->all(),
        );

        $this->travelTo(now()->addDay());
        $this->assertSame(2, app(AddNewCards::class)->handle($user));
        $this->assertSame(0, Word::whereNotIn('id', $user->cards()->select('word_id'))->count());
    }

    #[DataProvider('quotas')]
    public function test_the_quota_follows_the_stage_and_week(int $day, int $quota): void
    {
        Word::factory()->count(20)->level(null)->create();

        $this->assertSame($quota, app(AddNewCards::class)->handle($this->userOnDay($day)));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function quotas(): array
    {
        return [
            'stage 2' => [57, 10],
            'stage 3' => [183, 12],
            'stage 4' => [330, 15],
            'stage 5' => [484, 10],
            'stage 2, revision week 13' => [85, 0],
            'stage 5, mock test week 73' => [505, 5],
            'stage 6' => [666, 0],
            'rest day' => [7, 0],
            'after the last day' => [729, 0],
        ];
    }

    public function test_nothing_is_added_before_the_roadmap_starts(): void
    {
        Word::factory()->count(10)->create();
        $notStarted = User::factory()->create(['roadmap_start_date' => null])->refresh();
        $startsTomorrow = User::factory()->create(['roadmap_start_date' => '2026-10-30'])->refresh();

        $this->assertSame(0, app(AddNewCards::class)->handle($notStarted));
        $this->assertSame(0, app(AddNewCards::class)->handle($startsTomorrow));
        $this->assertSame(0, Card::count());
    }

    /**
     * A learner whose roadmap day is $day today (29/10 in Vietnam).
     */
    private function userOnDay(int $day): User
    {
        return User::factory()->create([
            'roadmap_start_date' => now('Asia/Ho_Chi_Minh')->subDays($day - 1)->toDateString(),
        ])->refresh();
    }
}
