<?php

namespace Tests\Feature\Database;

use App\Models\Month;
use App\Models\RoadmapDay;
use App\Models\RoadmapResource;
use App\Models\Stage;
use App\Models\Week;
use Database\Seeders\RoadmapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadmapSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_the_whole_roadmap(): void
    {
        $this->seed(RoadmapSeeder::class);

        $this->assertSame(30, RoadmapResource::count());
        $this->assertSame(6, Stage::count());
        $this->assertSame(24, Month::count());
        $this->assertSame(104, Week::count());
        $this->assertSame(728, RoadmapDay::count());
    }

    public function test_days_link_up_to_their_week_month_and_stage(): void
    {
        $this->seed(RoadmapSeeder::class);

        $first = RoadmapDay::where('number', 1)->sole();
        $this->assertSame(1, $first->day);
        $this->assertFalse($first->is_rest);
        $this->assertSame(1, $first->week->number);
        $this->assertSame(1, $first->week->month->number);
        $this->assertSame(1, $first->week->month->stage->number);

        $last = RoadmapDay::where('number', 728)->sole();
        $this->assertSame(7, $last->day);
        $this->assertTrue($last->is_rest);
        $this->assertSame(104, $last->week->number);
        $this->assertSame(24, $last->week->month->number);
        $this->assertSame(6, $last->week->month->stage->number);
    }

    public function test_json_columns_come_back_as_arrays(): void
    {
        $this->seed(RoadmapSeeder::class);

        $stage = Stage::where('number', 1)->sole();
        $this->assertSame('Phát âm + A1', $stage->title);
        $this->assertSame(120, array_sum(array_column($stage->blocks, 'minutes')));
        $this->assertNotEmpty($stage->notes);

        $week = Week::where('number', 1)->sole();
        $this->assertSame('Trọng tâm', $week->details[0]['label']);
    }

    public function test_running_it_again_updates_rows_in_place(): void
    {
        $this->seed(RoadmapSeeder::class);
        $day = RoadmapDay::where('number', 100)->sole();
        $day->update(['task' => 'edited by hand']);

        $this->seed(RoadmapSeeder::class);

        $this->assertSame(728, RoadmapDay::count());
        $this->assertSame(104, Week::count());
        $reloaded = RoadmapDay::where('number', 100)->sole();
        $this->assertSame($day->id, $reloaded->id);
        $this->assertNotSame('edited by hand', $reloaded->task);
    }
}
