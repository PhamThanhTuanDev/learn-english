<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Word;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_the_page_adds_todays_new_words_and_queues_old_due_cards_first(): void
    {
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-29'])->refresh();
        $old = $this->card($user, ['due_on' => '2026-10-29', 'last_reviewed_at' => now()->subDay()], 'better');
        $overdue = $this->card($user, ['due_on' => '2026-10-20', 'last_reviewed_at' => now()->subDays(9)], 'thus');
        $this->card($user, ['due_on' => '2026-10-30', 'last_reviewed_at' => now()], 'later');
        $this->card(User::factory()->create(), ['due_on' => '2026-10-29'], 'other');
        // Old cards: created today they would use up today's new words.
        Card::query()->update(['created_at' => now()->subDays(10)]);
        $new = Word::factory()->count(8)->create(['example' => 'An example.']);

        $response = $this->actingAs($user)->get('/flashcards');

        $response->assertOk();
        $response->assertViewHas('cards', function (array $cards) use ($old, $overdue, $new) {
            $newCards = Card::whereIn('word_id', $new->modelKeys())->orderBy('id')->pluck('id')->all();
            $this->assertCount(8, $newCards);
            $this->assertSame([$overdue->id, $old->id, ...$newCards], array_column($cards, 'id'));
            $this->assertSame(['thus', false], [$cards[0]['word'], $cards[0]['is_new']]);
            $this->assertSame(['An example.', true], [$cards[2]['example'], $cards[2]['is_new']]);
            $this->assertSame(route('flashcards.review', $overdue), $cards[0]['url']);

            return true;
        });
        $response->assertSeeInOrder(['Quên', 'Khó', 'Nhớ', 'Dễ']);
    }

    public function test_without_a_start_date_the_page_explains_where_new_words_come_from(): void
    {
        Word::factory()->count(8)->create();
        $user = User::factory()->create(['roadmap_start_date' => null]);

        $response = $this->actingAs($user)->get('/flashcards');

        $response->assertOk();
        $response->assertSee('bạn chưa chọn ngày bắt đầu');
        $response->assertViewHas('cards', []);
        $this->assertSame(0, Card::count());
    }

    public function test_an_answer_reschedules_the_card_and_tells_whether_it_is_still_due_today(): void
    {
        $user = User::factory()->create()->refresh();
        $card = $this->card($user, ['due_on' => '2026-10-29']);

        $this->actingAs($user)
            ->postJson(route('flashcards.review', $card), ['rating' => 3])
            ->assertOk()
            ->assertExactJson(['due_on' => '2026-10-30', 'due_today' => false]);

        $this->actingAs($user)
            ->postJson(route('flashcards.review', $card), ['rating' => 1])
            ->assertOk()
            ->assertExactJson(['due_on' => '2026-10-29', 'due_today' => true]);

        $this->assertSame(2, $user->cardReviews()->count());
    }

    public function test_answers_need_a_valid_rating_and_the_learners_own_card(): void
    {
        $user = User::factory()->create()->refresh();
        $card = $this->card(User::factory()->create(), ['due_on' => '2026-10-29']);
        $own = $this->card($user, ['due_on' => '2026-10-29'], 'thus');

        $this->post(route('flashcards.review', $own), ['rating' => 3])->assertRedirect('/login');
        $this->actingAs($user)->postJson(route('flashcards.review', $card), ['rating' => 3])->assertNotFound();
        $this->actingAs($user)->postJson(route('flashcards.review', $own), ['rating' => 5])->assertUnprocessable();
        $this->actingAs($user)->postJson(route('flashcards.review', $own), [])->assertUnprocessable();

        $this->assertSame(0, $user->cardReviews()->count());
        $this->assertNull($own->refresh()->last_reviewed_at);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function card(User $user, array $attributes, string $word = 'better'): Card
    {
        // Without a level, these words come after the A1 ones as new words.
        $word = Word::firstOrCreate(['word' => $word, 'part_of_speech' => 'adjective'], ['meaning' => 'nghĩa']);

        return $user->cards()->create(['word_id' => $word->id, ...$attributes]);
    }
}
