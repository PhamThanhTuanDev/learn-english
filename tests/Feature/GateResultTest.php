<?php

namespace Tests\Feature;

use App\Models\Month;
use App\Models\User;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateResultTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Month $month;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoadmapSeeder::class);

        // 18:30 UTC on 28/10 is already 01:30 on 29/10 in Vietnam.
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 28, 18, 30));

        // 01/10 is day 1, so today is day 29: the gate of month 1 (opens on day 22) is open.
        $this->user = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();
        $this->month = Month::where('number', 1)->firstOrFail();
    }

    public function test_the_learner_records_a_gate_result(): void
    {
        $response = $this->actingAs($this->user)->from('/roadmap/months/1')
            ->put('/months/1/gate', ['passed' => '1', 'note' => 'Đọc đúng 19/20 nguyên âm']);

        $response->assertRedirect('/roadmap/months/1');
        $response->assertSessionHas('status', 'gate-saved');
        $result = $this->user->gateResults()->sole();
        $this->assertSame($this->month->id, $result->month_id);
        $this->assertTrue($result->passed);
        $this->assertSame('Đọc đúng 19/20 nguyên âm', $result->note);
        $this->assertTrue($result->checked_at->equalTo(now()));
    }

    public function test_taking_the_gate_again_overwrites_the_result(): void
    {
        $this->user->gateResults()->create(['month_id' => $this->month->id, 'passed' => false, 'note' => 'Lần 1', 'checked_at' => '2026-10-24 10:00:00']);

        $this->actingAs($this->user)->put('/months/1/gate', ['passed' => '1', 'note' => '']);

        $result = $this->user->gateResults()->sole();
        $this->assertTrue($result->passed);
        $this->assertNull($result->note);
        $this->assertTrue($result->checked_at->equalTo(now()));
    }

    public function test_the_gate_opens_on_the_first_day_of_the_months_last_week(): void
    {
        // Day 21 today: still week 3.
        $early = User::factory()->create(['roadmap_start_date' => '2026-10-09'])->refresh();
        // Day 22 today: day 1 of week 4.
        $onTime = User::factory()->create(['roadmap_start_date' => '2026-10-08'])->refresh();

        $this->actingAs($early)->put('/months/1/gate', ['passed' => '1'])->assertForbidden();
        $this->actingAs($onTime)->put('/months/1/gate', ['passed' => '1'])->assertRedirect();

        $this->assertSame(0, $early->gateResults()->count());
        $this->assertSame(1, $onTime->gateResults()->count());
    }

    public function test_a_future_month_or_a_missing_start_date_is_forbidden(): void
    {
        $noStart = User::factory()->create()->refresh();

        $this->actingAs($this->user)->put('/months/2/gate', ['passed' => '1'])->assertForbidden();
        $this->actingAs($noStart)->put('/months/1/gate', ['passed' => '1'])->assertForbidden();

        $this->assertDatabaseCount('gate_results', 0);
    }

    public function test_the_result_and_note_are_validated(): void
    {
        $this->actingAs($this->user)->put('/months/1/gate', [])->assertSessionHasErrors('passed');
        $this->actingAs($this->user)->put('/months/1/gate', ['passed' => 'maybe'])->assertSessionHasErrors('passed');
        $this->actingAs($this->user)->put('/months/1/gate', ['passed' => '0', 'note' => str_repeat('a', 2001)])->assertSessionHasErrors('note');

        $this->assertDatabaseCount('gate_results', 0);
    }

    public function test_deleting_removes_only_the_learners_result_for_that_month(): void
    {
        $other = User::factory()->create(['roadmap_start_date' => '2026-10-01'])->refresh();
        $monthTwo = Month::where('number', 2)->value('id');
        $this->user->gateResults()->create(['month_id' => $this->month->id, 'passed' => true, 'checked_at' => now()]);
        $this->user->gateResults()->create(['month_id' => $monthTwo, 'passed' => true, 'checked_at' => now()]);
        $other->gateResults()->create(['month_id' => $this->month->id, 'passed' => true, 'checked_at' => now()]);

        $response = $this->actingAs($this->user)->from('/roadmap/months/1')->delete('/months/1/gate');

        $response->assertRedirect('/roadmap/months/1');
        $response->assertSessionHas('status', 'gate-deleted');
        $this->assertSame([$monthTwo], $this->user->gateResults()->pluck('month_id')->all());
        $this->assertSame(1, $other->gateResults()->count());
    }

    public function test_deleting_before_the_gate_opens_is_forbidden(): void
    {
        $this->actingAs($this->user)->delete('/months/2/gate')->assertForbidden();
    }

    public function test_unknown_months_are_not_found_and_guests_are_redirected(): void
    {
        $this->actingAs($this->user)->put('/months/25/gate', ['passed' => '1'])->assertNotFound();

        auth()->logout();

        $this->put('/months/1/gate', ['passed' => '1'])->assertRedirect('/login');
        $this->delete('/months/1/gate')->assertRedirect('/login');
    }
}
