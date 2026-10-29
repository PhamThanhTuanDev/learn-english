<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDayCompletionRequest;
use App\Models\RoadmapDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DayCompletionController extends Controller
{
    /**
     * Mark a roadmap day as done, or change the minutes of a day already done.
     */
    public function update(UpdateDayCompletionRequest $request, RoadmapDay $roadmapDay): RedirectResponse
    {
        $completion = $request->user()->dayCompletions()->firstOrNew(['roadmap_day_id' => $roadmapDay->id]);
        $completion->minutes = $request->integer('minutes');

        // Keep the moment of the first tick when only the minutes change.
        if (! $completion->exists) {
            $completion->completed_at = now();
        }

        $completion->save();

        return back()->with('status', 'day-saved');
    }

    /**
     * Untick a roadmap day.
     */
    public function destroy(Request $request, RoadmapDay $roadmapDay): RedirectResponse
    {
        abort_unless($request->user()->hasReachedRoadmapDay($roadmapDay->number), 403);

        $request->user()->dayCompletions()->where('roadmap_day_id', $roadmapDay->id)->delete();

        return back();
    }
}
