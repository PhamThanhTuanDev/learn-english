<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RoadmapDataTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $roadmap;

    protected function setUp(): void
    {
        parent::setUp();

        $json = file_get_contents(dirname(__DIR__, 2).'/database/data/roadmap.json');
        $this->roadmap = json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_it_has_6_stages_24_months_104_weeks_and_728_days(): void
    {
        $this->assertCount(6, $this->roadmap['stages']);
        $this->assertCount(24, $this->roadmap['months']);
        $this->assertCount(104, $this->roadmap['weeks']);
        $this->assertCount(728, $this->days());
    }

    public function test_numbers_run_without_gaps(): void
    {
        $this->assertSame(range(1, 6), array_column($this->roadmap['stages'], 'number'));
        $this->assertSame(range(1, 24), array_column($this->roadmap['months'], 'number'));
        $this->assertSame(range(1, 104), array_column($this->roadmap['weeks'], 'number'));
        $this->assertSame(range(1, 728), array_column($this->days(), 'number'));
    }

    public function test_every_week_lies_inside_its_month_and_stage(): void
    {
        $months = array_column($this->roadmap['months'], null, 'number');

        foreach ($this->roadmap['weeks'] as $week) {
            $month = $months[$week['month']];

            $this->assertGreaterThanOrEqual($month['first_week'], $week['number']);
            $this->assertLessThanOrEqual($month['last_week'], $week['number']);
            $this->assertSame($month['stage'], $week['stage'], "Week {$week['number']}");
        }
    }

    public function test_each_week_has_six_study_days_then_a_rest_day(): void
    {
        foreach ($this->roadmap['weeks'] as $week) {
            $this->assertSame(range(1, 7), array_column($week['days'], 'day'), "Week {$week['number']}");
            $this->assertSame([false, false, false, false, false, false, true], array_column($week['days'], 'rest'), "Week {$week['number']}");

            foreach ($week['days'] as $day) {
                $this->assertNotSame('', trim($day['label']), "Day {$day['number']}");
                $this->assertNotSame('', trim($day['task']), "Day {$day['number']}");
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function days(): array
    {
        return array_merge(...array_column($this->roadmap['weeks'], 'days'));
    }
}
