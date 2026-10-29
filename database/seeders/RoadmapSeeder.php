<?php

namespace Database\Seeders;

use App\Models\Month;
use App\Models\RoadmapDay;
use App\Models\RoadmapResource;
use App\Models\Stage;
use App\Models\Week;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoadmapSeeder extends Seeder
{
    /**
     * Load the 104-week roadmap from database/data/roadmap.json (built by tools/roadmap/export-json.js).
     *
     * Rows are matched by code/number and updated in place, so their ids never change.
     * Runs on every deploy (D15).
     */
    public function run(): void
    {
        /** @var array{
         *     resources: list<array{code: string, name: string, source: string, usage: string}>,
         *     stages: list<array{number: int, title: string, goal: string, blocks: list<mixed>, notes: list<string>}>,
         *     months: list<array{number: int, stage: int, first_week: int, last_week: int, focus: string, gate: string}>,
         *     weeks: list<array{number: int, month: int, title: string, details: list<mixed>, days: list<array{number: int, day: int, label: string, task: string, rest: bool}>}>
         * } $roadmap
         */
        $roadmap = json_decode((string) file_get_contents(database_path('data/roadmap.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($roadmap) {
            RoadmapResource::upsert($roadmap['resources'], ['code'], ['name', 'source', 'usage']);

            Stage::upsert(array_map(fn (array $stage) => [
                'number' => $stage['number'],
                'title' => $stage['title'],
                'goal' => $stage['goal'],
                'blocks' => $this->json($stage['blocks']),
                'notes' => $this->json($stage['notes']),
            ], $roadmap['stages']), ['number'], ['title', 'goal', 'blocks', 'notes']);
            $stageIds = Stage::pluck('id', 'number');

            Month::upsert(array_map(fn (array $month) => [
                'number' => $month['number'],
                'stage_id' => $stageIds[$month['stage']],
                'first_week' => $month['first_week'],
                'last_week' => $month['last_week'],
                'focus' => $month['focus'],
                'gate' => $month['gate'],
            ], $roadmap['months']), ['number'], ['stage_id', 'first_week', 'last_week', 'focus', 'gate']);
            $monthIds = Month::pluck('id', 'number');

            Week::upsert(array_map(fn (array $week) => [
                'number' => $week['number'],
                'month_id' => $monthIds[$week['month']],
                'title' => $week['title'],
                'details' => $this->json($week['details']),
            ], $roadmap['weeks']), ['number'], ['month_id', 'title', 'details']);
            $weekIds = Week::pluck('id', 'number');

            $days = [];
            foreach ($roadmap['weeks'] as $week) {
                foreach ($week['days'] as $day) {
                    $days[] = [
                        'number' => $day['number'],
                        'week_id' => $weekIds[$week['number']],
                        'day' => $day['day'],
                        'label' => $day['label'],
                        'task' => $day['task'],
                        'is_rest' => $day['rest'],
                    ];
                }
            }

            // Chunked to stay under SQLite's limit on bound parameters.
            foreach (array_chunk($days, 100) as $chunk) {
                RoadmapDay::upsert($chunk, ['number'], ['week_id', 'day', 'label', 'task', 'is_rest']);
            }
        });
    }

    /**
     * Upsert skips model casts, so JSON columns are encoded here.
     */
    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
