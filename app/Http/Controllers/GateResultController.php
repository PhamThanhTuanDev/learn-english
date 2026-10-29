<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGateResultRequest;
use App\Models\Month;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GateResultController extends Controller
{
    /**
     * Record the month's gate result. Taking the gate again overwrites it.
     */
    public function update(UpdateGateResultRequest $request, Month $month): RedirectResponse
    {
        $request->user()->gateResults()->updateOrCreate(
            ['month_id' => $month->id],
            [
                'passed' => $request->boolean('passed'),
                'note' => $request->validated('note'),
                'checked_at' => now(),
            ],
        );

        return back()->with('status', 'gate-saved');
    }

    /**
     * Remove the month's gate result, so the month is unchecked again.
     */
    public function destroy(Request $request, Month $month): RedirectResponse
    {
        abort_unless($request->user()->hasReachedRoadmapDay($month->gateOpensOnDay()), 403);

        $request->user()->gateResults()->where('month_id', $month->id)->delete();

        return back()->with('status', 'gate-deleted');
    }
}
