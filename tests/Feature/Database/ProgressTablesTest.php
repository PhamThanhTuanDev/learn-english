<?php

namespace Tests\Feature\Database;

use App\Models\DayCompletion;
use App\Models\GateResult;
use App\Models\Month;
use App\Models\RoadmapDay;
use App\Models\User;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);
    }

    public function test_a_day_completion_links_the_user_and_the_roadmap_day(): void
    {
        $user = User::factory()->create();
        $day = RoadmapDay::where('number', 3)->sole();

        $user->dayCompletions()->create([
            'roadmap_day_id' => $day->id,
            'minutes' => 45,
            'completed_at' => '2026-10-29 15:30:00',
        ]);

        $completion = DayCompletion::sole();
        $this->assertSame(45, $completion->minutes);
        $this->assertSame('2026-10-29 15:30:00', $completion->completed_at->toDateTimeString());
        $this->assertTrue($completion->user->is($user));
        $this->assertTrue($completion->roadmapDay->is($day));
        $this->assertTrue($user->dayCompletions()->sole()->is($completion));
        $this->assertTrue($day->completions()->sole()->is($completion));
    }

    public function test_a_user_cannot_complete_the_same_day_twice(): void
    {
        $user = User::factory()->create();
        $day = RoadmapDay::where('number', 1)->sole();
        $user->dayCompletions()->create(['roadmap_day_id' => $day->id, 'completed_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);

        $user->dayCompletions()->create(['roadmap_day_id' => $day->id, 'completed_at' => now()]);
    }

    public function test_different_users_can_complete_the_same_day(): void
    {
        $day = RoadmapDay::where('number', 1)->sole();

        foreach (User::factory()->count(2)->create() as $user) {
            $user->dayCompletions()->create(['roadmap_day_id' => $day->id, 'completed_at' => now()]);
        }

        $this->assertSame(2, $day->completions()->count());
    }

    public function test_a_gate_result_links_the_user_and_the_month(): void
    {
        $user = User::factory()->create();
        $month = Month::where('number', 2)->sole();

        $user->gateResults()->create([
            'month_id' => $month->id,
            'passed' => false,
            'note' => 'Học lại tuần 6 và 7.',
            'checked_at' => '2026-12-27 09:00:00',
        ]);

        $result = GateResult::sole();
        $this->assertFalse($result->passed);
        $this->assertSame('Học lại tuần 6 và 7.', $result->note);
        $this->assertSame('2026-12-27 09:00:00', $result->checked_at->toDateTimeString());
        $this->assertTrue($result->user->is($user));
        $this->assertTrue($result->month->is($month));
        $this->assertTrue($user->gateResults()->sole()->is($result));
        $this->assertTrue($month->gateResults()->sole()->is($result));
    }

    public function test_a_user_has_one_gate_result_per_month(): void
    {
        $user = User::factory()->create();
        $month = Month::where('number', 1)->sole();
        $user->gateResults()->create(['month_id' => $month->id, 'passed' => false, 'checked_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);

        $user->gateResults()->create(['month_id' => $month->id, 'passed' => true, 'checked_at' => now()]);
    }

    public function test_deleting_a_user_deletes_their_progress(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $dayId = RoadmapDay::where('number', 1)->value('id');
        $monthId = Month::where('number', 1)->value('id');

        foreach ([$user, $other] as $learner) {
            $learner->dayCompletions()->create(['roadmap_day_id' => $dayId, 'completed_at' => now()]);
            $learner->gateResults()->create(['month_id' => $monthId, 'passed' => true, 'checked_at' => now()]);
        }

        $user->delete();

        $this->assertSame([$other->id], DayCompletion::pluck('user_id')->all());
        $this->assertSame([$other->id], GateResult::pluck('user_id')->all());
    }
}
