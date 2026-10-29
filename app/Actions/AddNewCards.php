<?php

namespace App\Actions;

use App\Models\Card;
use App\Models\RoadmapDay;
use App\Models\User;
use App\Models\Word;
use App\Support\NewWordPlan;

class AddNewCards
{
    /**
     * Turn today's new words into cards due today, up to the quota of the
     * learner's roadmap day (D21), and return how many were added.
     *
     * Safe to call on every visit: cards already added on the learner's
     * today count toward the quota. Missed days are not made up, and rest
     * days add nothing.
     */
    public function handle(User $user): int
    {
        $number = $user->roadmapDayNumber();
        $day = $number === null ? null : RoadmapDay::with('week.month.stage')->where('number', $number)->first();

        if ($day === null || $day->is_rest) {
            return 0;
        }

        $stage = $day->week->month->stage->number;
        $missing = NewWordPlan::quota($stage, $day->week->number) - $this->addedToday($user);

        if ($missing <= 0) {
            return 0;
        }

        $order = NewWordPlan::levelOrder($stage);
        $ranks = implode(' ', array_map(fn (int $rank) => "when ? then {$rank}", array_keys($order)));
        $wordIds = Word::query()
            ->whereNotIn('id', Card::query()->select('word_id')->where('user_id', $user->id))
            ->orderByRaw("case level {$ranks} else ".count($order).' end', $order)
            ->orderBy('id')
            ->limit($missing)
            ->pluck('id');

        $now = now();
        $dueOn = $user->localNow()->toDateString();

        // A second request picks the same words, so ignoring duplicates
        // cannot push the day over its quota.
        return Card::query()->insertOrIgnore($wordIds->map(fn (int $wordId) => [
            'user_id' => $user->id,
            'word_id' => $wordId,
            'due_on' => $dueOn,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Cards created since midnight in the learner's timezone.
     */
    private function addedToday(User $user): int
    {
        $start = $user->localNow()->startOfDay()->utc();

        return $user->cards()->where('created_at', '>=', $start)->count();
    }
}
