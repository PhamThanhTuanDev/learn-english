<?php

namespace App\Support;

use App\Models\DayCompletion;
use App\Models\Month;
use App\Models\RoadmapDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * The learner's progress figures shown on the dashboard (D18).
 */
final class StudyStats
{
    /**
     * $months lists the roadmap months reached so far; share is the month's
     * minutes as a percentage of the busiest month's.
     *
     * @param  list<array{number: int, days: int, minutes: int, share: int, current: bool}>  $months
     */
    public function __construct(
        public readonly int $doneDays,
        public readonly int $totalDays,
        public readonly int $totalMinutes,
        public readonly int $currentStreak,
        public readonly int $longestStreak,
        public readonly int $gatesPassed,
        public readonly int $totalGates,
        public readonly array $months,
    ) {}

    public static function for(User $user): self
    {
        $completions = $user->dayCompletions()->with('roadmapDay:id,number')->get();
        $months = Month::orderBy('number')->get();
        [$currentStreak, $longestStreak] = self::streaks($user, $completions);

        return new self(
            doneDays: $completions->count(),
            totalDays: RoadmapDay::count(),
            totalMinutes: (int) $completions->sum('minutes'),
            currentStreak: $currentStreak,
            longestStreak: $longestStreak,
            gatesPassed: $user->gateResults()->where('passed', true)->count(),
            totalGates: $months->count(),
            months: self::monthlyMinutes($months, $completions, $user->roadmapDayNumber()),
        );
    }

    public function percentDone(): float
    {
        return $this->totalDays === 0 ? 0.0 : $this->doneDays / $this->totalDays * 100;
    }

    public function averageMinutes(): int
    {
        return $this->doneDays === 0 ? 0 : (int) round($this->totalMinutes / $this->doneDays);
    }

    /**
     * Minutes as hours and minutes for the page, e.g. "2 giờ 30 phút".
     */
    public static function duration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return match (true) {
            $hours === 0 => "{$rest} phút",
            $rest === 0 => "{$hours} giờ",
            default => "{$hours} giờ {$rest} phút",
        };
    }

    /**
     * The current and the longest run of calendar days, by the learner's
     * clock, with at least one tick. A rest day of the roadmap without a tick
     * neither breaks nor extends a run, and neither does today until it ends.
     * A made-up day counts for the date it was ticked, not its own date.
     *
     * @param  Collection<int, DayCompletion>  $completions
     * @return array{int, int}
     */
    private static function streaks(User $user, Collection $completions): array
    {
        if ($completions->isEmpty()) {
            return [0, 0];
        }

        $ticked = array_flip($completions
            ->map(fn (DayCompletion $completion) => $completion->completed_at->copy()->setTimezone($user->timezone)->toDateString())
            ->all());

        $restDates = [];
        if ($user->roadmap_start_date !== null) {
            $start = CarbonImmutable::parse($user->roadmap_start_date->toDateString());
            foreach (RoadmapDay::where('is_rest', true)->pluck('number') as $number) {
                $restDates[$start->addDays($number - 1)->toDateString()] = true;
            }
        }

        $today = $user->localNow()->toDateString();
        $run = 0;
        $longest = 0;

        for ($date = CarbonImmutable::parse((string) min(array_keys($ticked))); $date->toDateString() <= $today; $date = $date->addDay()) {
            $key = $date->toDateString();

            if (isset($ticked[$key])) {
                $run++;
                $longest = max($longest, $run);
            } elseif (! isset($restDates[$key]) && $key !== $today) {
                $run = 0;
            }
        }

        return [$run, $longest];
    }

    /**
     * Days done and minutes per roadmap month. A made-up day counts for the
     * month it belongs to. Months not reached yet are left out.
     *
     * @param  Collection<int, Month>  $months
     * @param  Collection<int, DayCompletion>  $completions
     * @return list<array{number: int, days: int, minutes: int, share: int, current: bool}>
     */
    private static function monthlyMinutes(Collection $months, Collection $completions, ?int $today): array
    {
        $rows = [];

        foreach ($months as $month) {
            $firstDay = ($month->first_week - 1) * 7 + 1;
            $lastDay = $month->last_week * 7;
            $done = $completions->filter(fn (DayCompletion $completion) => $completion->roadmapDay->number >= $firstDay
                && $completion->roadmapDay->number <= $lastDay);

            if ($done->isEmpty() && ($today === null || $today < $firstDay)) {
                continue;
            }

            $rows[] = [
                'number' => $month->number,
                'days' => $done->count(),
                'minutes' => (int) $done->sum('minutes'),
                'current' => $today !== null && $today >= $firstDay && $today <= $lastDay,
            ];
        }

        $busiest = max([0, ...array_column($rows, 'minutes')]);

        return array_map(fn (array $row) => $row + [
            'share' => $busiest === 0 ? 0 : (int) round($row['minutes'] / $busiest * 100),
        ], $rows);
    }
}
