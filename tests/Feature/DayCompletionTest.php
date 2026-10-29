<?php

namespace Tests\Feature;

use App\Models\DayCompletion;
use App\Models\RoadmapDay;
use App\Models\User;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DayCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam: day 3.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));
        $this->user = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();
    }

    public function test_ticking_today_records_the_minutes_and_the_time(): void
    {
        $response = $this->actingAs($this->user)->from('/dashboard')->put('/days/3/completion', ['minutes' => 45]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('status', 'day-saved');
        $completion = $this->user->dayCompletions()->sole();
        $this->assertSame(3, $completion->roadmapDay->number);
        $this->assertSame(45, $completion->minutes);
        $this->assertSame('2026-10-28 18:30:00', $completion->completed_at->toDateTimeString());
    }

    public function test_saving_again_changes_the_minutes_but_keeps_the_first_tick_time(): void
    {
        $this->actingAs($this->user)->put('/days/3/completion', ['minutes' => 45]);
        $this->travel(2)->hours();

        $this->actingAs($this->user)->put('/days/3/completion', ['minutes' => 90]);

        $completion = $this->user->dayCompletions()->sole();
        $this->assertSame(90, $completion->minutes);
        $this->assertSame('2026-10-28 18:30:00', $completion->completed_at->toDateTimeString());
    }

    public function test_an_earlier_day_can_be_made_up(): void
    {
        $this->actingAs($this->user)->put('/days/1/completion', ['minutes' => 30])->assertRedirect();

        $this->assertSame(1, $this->user->dayCompletions()->sole()->roadmapDay->number);
    }

    public function test_a_future_day_is_locked(): void
    {
        $this->actingAs($this->user)->put('/days/4/completion', ['minutes' => 30])->assertForbidden();
        $this->actingAs($this->user)->delete('/days/4/completion')->assertForbidden();

        $this->assertSame(0, DayCompletion::count());
    }

    public function test_no_day_can_be_ticked_without_a_start_date(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->put('/days/1/completion', ['minutes' => 30])->assertForbidden();
    }

    public function test_an_unknown_day_is_not_found(): void
    {
        $this->actingAs($this->user)->put('/days/729/completion', ['minutes' => 30])->assertNotFound();
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidMinutes(): array
    {
        return [
            'missing' => [''],
            'not a number' => ['abc'],
            'negative' => [-1],
            'too many' => [601],
        ];
    }

    #[DataProvider('invalidMinutes')]
    public function test_minutes_must_be_between_0_and_600(mixed $minutes): void
    {
        $response = $this->actingAs($this->user)->put('/days/3/completion', ['minutes' => $minutes]);

        $response->assertSessionHasErrors('minutes');
        $this->assertSame(0, DayCompletion::count());
    }

    public function test_unticking_removes_only_the_users_own_completion(): void
    {
        $other = User::factory()->create(['roadmap_start_date' => '2026-10-27'])->refresh();
        $dayId = RoadmapDay::where('number', 1)->value('id');
        foreach ([$this->user, $other] as $learner) {
            $learner->dayCompletions()->create(['roadmap_day_id' => $dayId, 'completed_at' => now()]);
        }

        $response = $this->actingAs($this->user)->from('/dashboard')->delete('/days/1/completion');

        $response->assertRedirect('/dashboard');
        $this->assertSame([$other->id], DayCompletion::pluck('user_id')->all());
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->put('/days/1/completion', ['minutes' => 30])->assertRedirect('/login');
        $this->delete('/days/1/completion')->assertRedirect('/login');
    }
}
