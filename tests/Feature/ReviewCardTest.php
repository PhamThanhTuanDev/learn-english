<?php

namespace Tests\Feature;

use App\Actions\ReviewCard;
use App\Enums\Rating;
use App\Models\Card;
use App\Models\CardReview;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_a_good_answer_schedules_from_the_learners_today_and_is_logged(): void
    {
        $user = User::factory()->create()->refresh();
        $card = $this->card($user);

        $review = app(ReviewCard::class)->handle($card, Rating::Good);

        $card->refresh();
        // Tomorrow in Vietnam is 30/10; by the UTC date it would be 29/10.
        $this->assertSame('2026-10-30', $card->due_on->toDateString());
        $this->assertSame(1, $card->interval_days);
        $this->assertSame(1, $card->repetitions);
        $this->assertSame(2.5, $card->ease_factor);
        $this->assertSame(0, $card->lapses);
        $this->assertSame('2026-10-28 18:30:00', $card->last_reviewed_at?->toDateTimeString());

        $this->assertTrue($review->is($user->cardReviews()->sole()));
        $this->assertSame($card->id, $review->card_id);
        $this->assertSame(Rating::Good, $review->rating);
        $this->assertSame(1, $review->interval_days);
        $this->assertSame(2.5, $review->ease_factor);
        $this->assertSame('2026-10-30', $review->due_on->toDateString());
        $this->assertSame('2026-10-28 18:30:00', $review->reviewed_at->toDateTimeString());
    }

    public function test_forgetting_a_learned_card_counts_a_lapse_and_keeps_it_due_today(): void
    {
        $user = User::factory()->create()->refresh();
        $card = $this->card($user, ['ease_factor' => 2.36, 'interval_days' => 15, 'repetitions' => 3, 'lapses' => 1]);

        app(ReviewCard::class)->handle($card, Rating::Again);

        $card->refresh();
        $this->assertSame('2026-10-29', $card->due_on->toDateString());
        $this->assertSame(0, $card->interval_days);
        $this->assertSame(0, $card->repetitions);
        $this->assertSame(2.36, $card->ease_factor);
        $this->assertSame(2, $card->lapses);

        // Seen again later in the same session, it starts over from 1 day.
        app(ReviewCard::class)->handle($card, Rating::Good);

        $card->refresh();
        $this->assertSame('2026-10-30', $card->due_on->toDateString());
        $this->assertSame(1, $card->repetitions);
        $this->assertSame(2, $card->lapses);
        $this->assertSame([Rating::Again, Rating::Good], $card->reviews()->orderBy('id')->pluck('rating')->all());
    }

    public function test_again_on_a_new_card_is_not_a_lapse(): void
    {
        $user = User::factory()->create()->refresh();
        $card = $this->card($user);

        app(ReviewCard::class)->handle($card, Rating::Again);

        $this->assertSame(0, $card->refresh()->lapses);
        $this->assertSame('2026-10-29', $card->due_on->toDateString());
    }

    public function test_deleting_the_learner_deletes_their_cards_and_reviews_but_not_the_words(): void
    {
        $user = User::factory()->create()->refresh();
        app(ReviewCard::class)->handle($this->card($user), Rating::Good);

        $user->delete();

        $this->assertSame(0, Card::count());
        $this->assertSame(0, CardReview::count());
        $this->assertSame(1, Word::count());
    }

    /**
     * A card due today (29/10 in Vietnam), new unless attributes say otherwise.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function card(User $user, array $attributes = []): Card
    {
        $word = Word::create(['word' => 'better', 'part_of_speech' => 'adjective', 'meaning' => 'hơn, tốt hơn']);

        return $user->cards()->create(['word_id' => $word->id, 'due_on' => '2026-10-29', ...$attributes])->refresh();
    }
}
