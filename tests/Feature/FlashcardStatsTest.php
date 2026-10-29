<?php

namespace Tests\Feature;

use App\Enums\Rating;
use App\Models\Card;
use App\Models\User;
use App\Models\Word;
use App\Support\FlashcardStats;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FlashcardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_it_counts_due_learned_and_known_cards_todays_answers_and_the_next_days(): void
    {
        $user = $this->learner();
        $reviewed = now()->subDays(3);
        $dueToday = $this->card($user, ['due_on' => '2026-10-29', 'interval_days' => 6, 'last_reviewed_at' => $reviewed]);
        $this->card($user, ['due_on' => '2026-10-20', 'interval_days' => 25, 'last_reviewed_at' => $reviewed]);
        $this->card($user, ['due_on' => '2026-10-29']);
        $this->card($user, ['due_on' => '2026-10-30', 'interval_days' => 30, 'last_reviewed_at' => $reviewed]);
        $this->card($user, ['due_on' => '2026-11-05', 'interval_days' => 8, 'last_reviewed_at' => $reviewed]);
        $this->card($user, ['due_on' => '2026-11-06', 'interval_days' => 9, 'last_reviewed_at' => $reviewed]);
        $this->card(User::factory()->create(), ['due_on' => '2026-10-29']);
        // Old cards, so they do not use up today's new words.
        Card::query()->update(['created_at' => now()->subDays(10)]);

        // 01:00 on 29/10 in Vietnam counts for today; 23:00 on 28/10 does not.
        $this->review($dueToday, Rating::Good, 1, now()->setTime(18, 0));
        $this->review($dueToday, Rating::Good, 1, now()->setTime(16, 0));

        $stats = FlashcardStats::for($user);

        $this->assertSame([3, 1, 8, 1], [$stats->due, $stats->dueNew, $stats->pendingNew, $stats->answeredToday]);
        $this->assertSame([5, 2], [$stats->learned, $stats->known]);
        $this->assertSame(
            [['2026-10-30', 1, 100], ['2026-10-31', 0, 0], ['2026-11-01', 0, 0], ['2026-11-02', 0, 0], ['2026-11-03', 0, 0], ['2026-11-04', 0, 0], ['2026-11-05', 1, 100]],
            array_map(fn (array $day) => [$day['date']->toDateString(), $day['cards'], $day['share']], $stats->forecast),
        );
    }

    public function test_retention_counts_only_reviews_of_cards_remembered_last_time_within_30_days(): void
    {
        $user = $this->learner();
        $card = $this->card($user, ['due_on' => '2026-11-10']);
        $this->review($card, Rating::Good, 1, now()->subDays(40));
        $this->review($card, Rating::Good, 6, now()->subDays(35));
        $this->review($card, Rating::Again, 0, now()->subDays(20)); // counted, forgotten
        $this->review($card, Rating::Good, 1, now()->subDays(20)->addMinute()); // retry after Again
        $this->review($card, Rating::Hard, 6, now()->subDays(10)); // counted
        $this->review($card, Rating::Easy, 15, now()->subDays(2)); // counted

        $other = $this->card($user, ['due_on' => '2026-10-30']);
        $this->review($other, Rating::Again, 0, now()->subDays(5)); // first sight
        $this->review($other, Rating::Good, 1, now()->subDays(5)->addMinute());
        $this->review($other, Rating::Good, 6, now()->subMinute()); // counted

        $someoneElse = $this->card(User::factory()->create(), ['due_on' => '2026-10-30']);
        $this->review($someoneElse, Rating::Good, 1, now()->subDays(3));
        $this->review($someoneElse, Rating::Again, 0, now()->subDays(2));

        $stats = FlashcardStats::for($user);

        $this->assertSame([4, 3], [$stats->retentionReviews, $stats->retentionPassed]);
        $this->assertSame(75.0, $stats->retentionPercent());
        $this->assertNull(FlashcardStats::for($this->learner())->retentionPercent());
    }

    public function test_the_dashboard_counts_todays_new_words_without_adding_them(): void
    {
        Word::factory()->count(10)->create();
        $user = $this->learner();
        $this->card($user, ['due_on' => '2026-10-28', 'interval_days' => 1, 'last_reviewed_at' => now()->subDay()]);
        Card::query()->update(['created_at' => now()->subDays(10)]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSeeInOrder(['Flashcard', 'Hôm nay còn 9 thẻ cần ôn (8 từ mới).', 'đã học 1 từ, thuộc 0', 'tỉ lệ nhớ 30 ngày: chưa có', 'Ôn ngay']);
        $response->assertSee(route('flashcards'));
        $this->assertSame(1, Card::count());

        $this->actingAs($user)->get('/flashcards')
            ->assertOk()
            ->assertSeeInOrder(['Đã học', '1', 'Đã thuộc', '0', 'Tỉ lệ nhớ', '–', 'Thẻ đến hạn 7 ngày tới', 'Ngày mai']);
        $this->assertSame(9, Card::count());
    }

    public function test_the_figures_stay_hidden_before_the_roadmap_starts_and_before_any_review(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => null]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('cần ôn');
        $this->actingAs($user)->get('/flashcards')->assertOk()->assertDontSee('Thẻ đến hạn');
    }

    /**
     * A learner on day 1 (29/10 in Vietnam), which adds 8 new words.
     */
    private function learner(): User
    {
        return User::factory()->create(['roadmap_start_date' => '2026-10-29'])->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function card(User $user, array $attributes): Card
    {
        return $user->cards()->create(['word_id' => Word::factory()->level(null)->create()->id, ...$attributes]);
    }

    private function review(Card $card, Rating $rating, int $interval, Carbon $at): void
    {
        $card->user->cardReviews()->create([
            'card_id' => $card->id,
            'rating' => $rating,
            'ease_factor' => 2.5,
            'interval_days' => $interval,
            'due_on' => $at->copy()->addDays($interval),
            'reviewed_at' => $at,
        ]);
    }
}
