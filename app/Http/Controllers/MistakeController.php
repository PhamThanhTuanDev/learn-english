<?php

namespace App\Http\Controllers;

use App\Enums\MistakeCategory;
use App\Http\Requests\MistakeRequest;
use App\Models\Mistake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MistakeController extends Controller
{
    /**
     * The mistake log: a form to add a mistake, then the newest first. The
     * form starts on the current week and the last category used.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $mistakes = $user->mistakes()->latest()->latest('id')->paginate(30);

        return view('mistakes.index', [
            'user' => $user,
            'mistakes' => $mistakes,
            'categories' => MistakeCategory::cases(),
            'defaultWeek' => $user->roadmapWeekNumber(),
            'defaultCategory' => ($user->mistakes()->latest()->latest('id')->value('category') ?? MistakeCategory::Grammar)->value,
        ]);
    }

    public function store(MistakeRequest $request): RedirectResponse
    {
        $request->user()->mistakes()->create($request->safe()->except('occurrences'));

        return to_route('mistakes')->with('status', 'mistake-added');
    }

    public function edit(Request $request, Mistake $mistake): View
    {
        $this->ensureOwned($request, $mistake);

        return view('mistakes.edit', [
            'mistake' => $mistake,
            'categories' => MistakeCategory::cases(),
        ]);
    }

    public function update(MistakeRequest $request, Mistake $mistake): RedirectResponse
    {
        $this->ensureOwned($request, $mistake);
        $mistake->update($request->validated());

        return to_route('mistakes')->with('status', 'mistake-updated');
    }

    /**
     * The learner made the same mistake again.
     */
    public function repeat(Request $request, Mistake $mistake): RedirectResponse
    {
        $this->ensureOwned($request, $mistake);
        $mistake->increment('occurrences', 1, ['last_repeated_at' => now()]);

        return back()
            ->withFragment('mistake-'.$mistake->id)
            ->with(['status' => 'mistake-repeated', 'mistake_id' => $mistake->id]);
    }

    public function destroy(Request $request, Mistake $mistake): RedirectResponse
    {
        $this->ensureOwned($request, $mistake);
        $mistake->delete();

        return back()->with('status', 'mistake-deleted');
    }

    private function ensureOwned(Request $request, Mistake $mistake): void
    {
        abort_unless($mistake->user_id === $request->user()->id, 404);
    }
}
