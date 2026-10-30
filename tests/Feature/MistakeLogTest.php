<?php

namespace Tests\Feature;

use App\Enums\MistakeCategory;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MistakeLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_the_form_starts_on_the_current_week_and_the_last_category_used(): void
    {
        // 29/10 is day 15 of the roadmap: week 3.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-15'])->refresh();

        $this->actingAs($user)->get('/mistakes')
            ->assertOk()
            ->assertSee('Chưa có lỗi nào.')
            ->assertSee('name="week" type="number" min="1" max="104" value="3"', false)
            ->assertSee('<option value="grammar" selected>Ngữ pháp</option>', false);

        Mistake::factory()->for($user)->create(['category' => MistakeCategory::Spelling]);

        $this->actingAs($user)->get('/mistakes')
            ->assertSee('<option value="spelling" selected>Chính tả</option>', false);
    }

    public function test_the_list_shows_the_learners_own_mistakes_newest_first(): void
    {
        $user = User::factory()->create()->refresh();
        Mistake::factory()->for($user)->create(['wrong' => 'I am agree.', 'correct' => 'I agree.', 'created_at' => now()->subDay()]);
        Mistake::factory()->for($user)->create([
            'wrong' => 'He don\'t like it.',
            'correct' => 'He doesn\'t like it.',
            'category' => MistakeCategory::Grammar,
            'note' => 'thiếu -s ở ngôi thứ ba',
            'week' => 12,
            'occurrences' => 3,
        ]);
        Mistake::factory()->create(['wrong' => 'Someone else\'s mistake.']);

        $response = $this->actingAs($user)->get('/mistakes');

        $response->assertOk();
        $response->assertSee('2 lỗi, mới nhất ở trên.');
        // Created at 01:30 on 29/10 in Vietnam, 28/10 in UTC.
        $response->assertSeeInOrder(['Ngữ pháp', 'Tuần 12', '29/10/2026', 'He don&#039;t like it.', 'He doesn&#039;t like it.', 'thiếu -s ở ngôi thứ ba', 'Đã gặp', '3', 'I am agree.'], false);
        $response->assertDontSee('Someone else');
    }

    public function test_a_new_mistake_is_logged_once(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->post('/mistakes', [
            'wrong' => 'She go to work by bus.',
            'correct' => 'She goes to work by bus.',
            'category' => 'grammar',
            'note' => '',
            'week' => '5',
            'occurrences' => '9',
        ])->assertRedirect('/mistakes')->assertSessionHas('status', 'mistake-added');

        $mistake = $user->mistakes()->sole();
        $this->assertSame(['She go to work by bus.', 'She goes to work by bus.', MistakeCategory::Grammar, null, 5, 1], [
            $mistake->wrong, $mistake->correct, $mistake->category, $mistake->note, $mistake->week, $mistake->refresh()->occurrences,
        ]);
    }

    public function test_a_mistake_needs_both_sentences_a_known_category_and_a_roadmap_week(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)
            ->from('/mistakes')
            ->post('/mistakes', ['wrong' => '', 'correct' => 'Fine.', 'category' => 'style', 'week' => '105'])
            ->assertRedirect('/mistakes')
            ->assertSessionHasErrors(['wrong', 'category', 'week']);

        $this->assertSame(0, Mistake::count());
    }

    public function test_repeating_a_mistake_counts_it_again(): void
    {
        $user = User::factory()->create()->refresh();
        $mistake = Mistake::factory()->for($user)->create(['occurrences' => 2]);

        $this->actingAs($user)
            ->from('/mistakes?page=2')
            ->post(route('mistakes.repeat', $mistake))
            ->assertRedirect('/mistakes?page=2#mistake-'.$mistake->id);

        $mistake->refresh();
        $this->assertSame(3, $mistake->occurrences);
        $this->assertSame('2026-10-28 18:30:00', $mistake->last_repeated_at?->toDateTimeString());
    }

    public function test_a_mistake_can_be_edited_and_deleted(): void
    {
        $user = User::factory()->create()->refresh();
        $mistake = Mistake::factory()->for($user)->create();

        $this->actingAs($user)->get(route('mistakes.edit', $mistake))
            ->assertOk()
            ->assertSee('She go to work by bus.')
            ->assertSee('name="occurrences"', false);

        $this->actingAs($user)->patch(route('mistakes.update', $mistake), [
            'wrong' => 'I have went there.',
            'correct' => 'I have gone there.',
            'category' => 'grammar',
            'note' => 'quá khứ phân từ',
            'week' => '',
            'occurrences' => '4',
        ])->assertRedirect('/mistakes');

        $mistake->refresh();
        $this->assertSame(['I have went there.', 'quá khứ phân từ', null, 4], [$mistake->wrong, $mistake->note, $mistake->week, $mistake->occurrences]);

        $this->actingAs($user)->delete(route('mistakes.destroy', $mistake))->assertRedirect();
        $this->assertModelMissing($mistake);
    }

    public function test_other_learners_mistakes_cannot_be_touched(): void
    {
        $user = User::factory()->create()->refresh();
        $mistake = Mistake::factory()->create(['occurrences' => 1]);
        $valid = ['wrong' => 'x', 'correct' => 'y', 'category' => 'grammar', 'occurrences' => '5'];

        $this->actingAs($user)->get(route('mistakes.edit', $mistake))->assertNotFound();
        $this->actingAs($user)->patch(route('mistakes.update', $mistake), $valid)->assertNotFound();
        $this->actingAs($user)->post(route('mistakes.repeat', $mistake))->assertNotFound();
        $this->actingAs($user)->delete(route('mistakes.destroy', $mistake))->assertNotFound();

        $this->assertSame(1, $mistake->refresh()->occurrences);

        $mistake->user->delete();
        $this->assertModelMissing($mistake);
    }
}
