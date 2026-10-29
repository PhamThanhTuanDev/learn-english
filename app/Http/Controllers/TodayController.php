<?php

namespace App\Http\Controllers;

use App\Models\RoadmapDay;
use App\Models\RoadmapResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TodayController extends Controller
{
    /**
     * Show today's roadmap day: the task, the stage's fixed blocks and the week.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $number = $user->roadmapDayNumber();
        $day = $number === null ? null : RoadmapDay::with('week.month.stage')->where('number', $number)->first();

        if ($day === null) {
            return view('today', compact('user', 'number', 'day'));
        }

        $week = $day->week;
        $stage = $week->month->stage;
        $weekDays = $week->days()->orderBy('day')->get();
        $completions = $user->dayCompletions()
            ->whereIn('roadmap_day_id', $weekDays->modelKeys())
            ->get()
            ->keyBy('roadmap_day_id');

        return view('today', [
            'user' => $user,
            'number' => $number,
            'day' => $day,
            'week' => $week,
            'stage' => $stage,
            'weekDays' => $weekDays,
            'completions' => $completions,
            'resources' => $this->resourcesMentionedIn([
                $day->task,
                ...array_column($stage->blocks, 'description'),
                ...array_column($week->details, 'text'),
            ]),
        ]);
    }

    /**
     * The resources (R0–R29) the given texts refer to, in code order.
     *
     * @param  list<string>  $texts
     * @return Collection<int, RoadmapResource>
     */
    private function resourcesMentionedIn(array $texts): Collection
    {
        preg_match_all('/\bR\d+\b/', implode("\n", $texts), $matches);

        return RoadmapResource::whereIn('code', array_unique($matches[0]))
            ->get()
            ->sortBy(fn (RoadmapResource $resource) => (int) substr($resource->code, 1))
            ->values();
    }
}
