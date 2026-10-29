<?php

namespace App\Http\Controllers;

use App\Models\Month;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoadmapController extends Controller
{
    /**
     * List the 24 months by stage, with the days done and the gate result of each.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $months = Month::with('stage')
            ->withCount([
                'days',
                'days as done_days_count' => fn (Builder $query) => $query->whereHas('completions', fn (Builder $query) => $query->whereBelongsTo($user)),
            ])
            ->orderBy('number')
            ->get();

        return view('roadmap.index', [
            'user' => $user,
            'stages' => $months->groupBy('stage_id'),
            'gateResults' => $user->gateResults()->get()->keyBy('month_id'),
            'today' => $user->roadmapDayNumber(),
        ]);
    }
}
