<?php

namespace App\Http\Controllers;

use App\Models\Month;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthController extends Controller
{
    /**
     * Show a month: its weeks with the days done, and the end-of-month gate.
     */
    public function show(Request $request, Month $month): View
    {
        $user = $request->user();
        $month->load(['stage', 'weeks' => fn ($query) => $query->orderBy('number'), 'weeks.days' => fn ($query) => $query->orderBy('day')]);

        return view('roadmap.month', [
            'user' => $user,
            'month' => $month,
            'completions' => $user->dayCompletions()
                ->whereIn('roadmap_day_id', $month->weeks->flatMap->days->pluck('id'))
                ->get()
                ->keyBy('roadmap_day_id'),
            'gateResult' => $user->gateResults()->where('month_id', $month->id)->first(),
            'today' => $user->roadmapDayNumber(),
        ]);
    }
}
