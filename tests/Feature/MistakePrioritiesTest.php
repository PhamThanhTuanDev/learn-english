<?php

namespace Tests\Feature;

use App\Enums\MistakeCategory;
use App\Models\Mistake;
use App\Models\User;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MistakePrioritiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
    }

    public function test_the_list_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create()->refresh();
        Mistake::factory()->for($user)->count(2)->create(['wrong' => 'Grammar slip.']);
        Mistake::factory()->for($user)->create(['wrong' => 'Spelling slip.', 'category' => MistakeCategory::Spelling]);
        Mistake::factory()->create(['wrong' => 'Someone else.', 'category' => MistakeCategory::Timing]);

        $this->actingAs($user)->get('/mistakes?category=spelling')
            ->assertOk()
            ->assertSee('1 lỗi loại chính tả, mới nhất ở trên.')
            ->assertSeeInOrder(['Tất cả (3)', 'Ngữ pháp (2)', 'Chính tả (1)'])
            ->assertDontSee('Quản lý thời gian (')
            ->assertSee('Spelling slip.')
            ->assertDontSee('Grammar slip.');

        // An unknown category shows everything.
        $this->actingAs($user)->get('/mistakes?category=style')
            ->assertOk()
            ->assertSee('3 lỗi, mới nhất ở trên.');
    }

    public function test_mistakes_made_three_times_are_suggested_and_at_most_three_are_pinned(): void
    {
        $user = User::factory()->create()->refresh();
        $often = Mistake::factory()->for($user)->create(['wrong' => 'Often wrong.', 'occurrences' => 5]);
        $older = Mistake::factory()->for($user)->create(['wrong' => 'Older wrong.', 'occurrences' => 3, 'created_at' => now()->subWeek()]);
        $recent = Mistake::factory()->for($user)->create(['wrong' => 'Recent wrong.', 'occurrences' => 3, 'created_at' => now()->subWeeks(2)]);
        $recent->forceFill(['last_repeated_at' => now()->subDay()])->save();
        $rare = Mistake::factory()->for($user)->create(['wrong' => 'Rare wrong.', 'occurrences' => 2]);

        $this->assertSame([$often->id, $recent->id, $older->id], $user->mistakes()->suggested()->pluck('id')->all());
        $this->actingAs($user)->get('/mistakes')
            ->assertSeeInOrder(['Chưa ghim lỗi nào.', 'Gợi ý', 'Often wrong.', 'Recent wrong.', 'Older wrong.', 'Ghi lỗi mới']);

        foreach ([$recent, $often, $rare] as $mistake) {
            $this->travel(1)->minutes();
            $this->actingAs($user)->from('/mistakes')->post(route('mistakes.pin', $mistake))
                ->assertRedirect('/mistakes#priorities');
        }

        $this->actingAs($user)->from('/mistakes')->post(route('mistakes.pin', $older))
            ->assertRedirect('/mistakes#priorities')
            ->assertSessionHas('status', 'pin-limit');
        $this->assertSame([$recent->id, $often->id, $rare->id], $user->mistakes()->pinned()->pluck('id')->all());

        $this->actingAs($user)->get('/mistakes')
            ->assertSeeInOrder(['1.', 'Recent wrong.', '2.', 'Often wrong.', '3.', 'Rare wrong.', 'Ghi lỗi mới'])
            ->assertDontSee('Gợi ý');

        $this->actingAs($user)->delete(route('mistakes.unpin', $often))->assertRedirect();
        $this->assertSame([$recent->id, $rare->id], $user->mistakes()->pinned()->pluck('id')->all());
        $this->assertSame([$often->id, $older->id], $user->mistakes()->suggested()->pluck('id')->all());
    }

    public function test_other_learners_mistakes_cannot_be_pinned(): void
    {
        $user = User::factory()->create()->refresh();
        $mistake = Mistake::factory()->create();

        $this->actingAs($user)->post(route('mistakes.pin', $mistake))->assertNotFound();
        $this->actingAs($user)->delete(route('mistakes.unpin', $mistake))->assertNotFound();
        $this->assertNull($mistake->refresh()->pinned_at);
    }

    public function test_the_dashboard_shows_the_pinned_mistakes_and_asks_for_new_ones_on_day_6(): void
    {
        $this->seed(RoadmapSeeder::class);
        // 29/10 is day 3 of week 1 for this learner.
        $user = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Lỗi ưu tiên tuần này');

        $pinned = Mistake::factory()->for($user)->create(['wrong' => 'She go to work.', 'note' => 'thiếu -s']);
        $pinned->forceFill(['pinned_at' => now()])->save();

        $this->actingAs($user)->get('/dashboard')
            ->assertSeeInOrder(['Lỗi ưu tiên tuần này', '1.', 'She go to work.', 'She goes to work by bus.', 'Ngữ pháp · gặp 1 lần', 'thiếu -s', 'Mở Sổ lỗi'])
            ->assertDontSee('Hôm nay là Ngày 6');

        // Day 6: the reminder shows even with nothing pinned.
        $pinned->delete();
        $this->travel(3)->days();

        $this->actingAs($user)->get('/dashboard')
            ->assertSeeInOrder(['Lỗi ưu tiên tuần này', 'Hôm nay là Ngày 6', 'chọn 3 lỗi ưu tiên cho tuần sau', 'Chưa ghim lỗi nào.'])
            ->assertSee(route('mistakes').'#priorities');
    }
}
