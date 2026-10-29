<?php

namespace Tests\Unit;

use App\Support\NewWordPlan;
use PHPUnit\Framework\TestCase;

class NewWordPlanTest extends TestCase
{
    public function test_levels_are_drawn_from_the_stage_then_lower_then_higher_ones(): void
    {
        $this->assertSame(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'], NewWordPlan::levelOrder(1));
        $this->assertSame(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'], NewWordPlan::levelOrder(2));
        $this->assertSame(['B1', 'A1', 'A2', 'B2', 'C1', 'C2'], NewWordPlan::levelOrder(3));
        $this->assertSame(['B2', 'C1', 'A1', 'A2', 'B1', 'C2'], NewWordPlan::levelOrder(4));
        $this->assertSame(['C1', 'C2', 'A1', 'A2', 'B1', 'B2'], NewWordPlan::levelOrder(5));
        $this->assertSame([], NewWordPlan::levelOrder(6));
    }

    public function test_quotas_match_the_vocabulary_note_of_every_roadmap_week(): void
    {
        $json = file_get_contents(dirname(__DIR__, 2).'/database/data/roadmap.json');
        $roadmap = json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);

        foreach ($roadmap['weeks'] as $week) {
            $note = array_column($week['details'], 'text', 'label')['Từ vựng'] ?? '';

            if (preg_match('/(\d+) (?:từ mới|thẻ)\/ngày/u', $note, $matches)) {
                $expected = (int) $matches[1];
            } elseif (str_contains($note, 'giảm số thẻ mới')) {
                $expected = 5;
            } else {
                $expected = 0;
            }

            $this->assertSame($expected, NewWordPlan::quota($week['stage'], $week['number']), "Tuần {$week['number']}: {$note}");
        }
    }
}
