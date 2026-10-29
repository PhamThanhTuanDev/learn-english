<?php

namespace App\Http\Controllers;

use App\Models\Week;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeekController extends Controller
{
    /**
     * Show a week's seven days, so past days can be reviewed and made up.
     */
    public function show(Request $request, Week $week): View
    {
        $user = $request->user();
        $week->load('month.stage');
        $days = $week->days()->orderBy('day')->get();

        return view('roadmap.week', [
            'user' => $user,
            'week' => $week,
            'days' => $days,
            'completions' => $user->dayCompletions()
                ->whereIn('roadmap_day_id', $days->modelKeys())
                ->get()
                ->keyBy('roadmap_day_id'),
            'today' => $user->roadmapDayNumber(),
        ]);
    }
}
