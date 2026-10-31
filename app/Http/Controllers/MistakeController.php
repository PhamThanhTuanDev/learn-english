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
     * The mistake log: the pinned priorities with suggestions, a form to add
     * a mistake, then the newest first, optionally of one category. The form
     * starts on the current week and the last category used.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $category = MistakeCategory::tryFrom($request->string('category')->toString());
        $mistakes = $user->mistakes()
            ->when($category, fn ($query, MistakeCategory $category) => $query->where('category', $category))
            ->latest()
            ->latest('id')
            ->paginate(30)
            ->withQueryString();
        $pinned = $user->mistakes()->pinned()->get();

        return view('mistakes.index', [
            'user' => $user,
            'mistakes' => $mistakes,
            'category' => $category,
            'counts' => $user->mistakes()->toBase()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
            'pinned' => $pinned,
            'suggested' => $pinned->count() < Mistake::MAX_PINNED ? $user->mistakes()->suggested()->limit(5)->get() : collect(),
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

    /**
     * Make the mistake one of the priorities to fix, unless three already are.
     */
    public function pin(Request $request, Mistake $mistake): RedirectResponse
    {
        $this->ensureOwned($request, $mistake);

        if ($mistake->pinned_at === null) {
            if ($request->user()->mistakes()->whereNotNull('pinned_at')->count() >= Mistake::MAX_PINNED) {
                return back()->withFragment('priorities')->with('status', 'pin-limit');
            }

            $mistake->forceFill(['pinned_at' => now()])->save();
        }

        return back()->withFragment('priorities')->with('status', 'mistake-pinned');
    }

    public function unpin(Request $request, Mistake $mistake): RedirectResponse
    {
        $this->ensureOwned($request, $mistake);
        $mistake->forceFill(['pinned_at' => null])->save();

        return back()->withFragment('priorities')->with('status', 'mistake-unpinned');
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
