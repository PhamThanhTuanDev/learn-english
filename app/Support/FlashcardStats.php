<?php

namespace App\Support;

use App\Actions\AddNewCards;
use App\Enums\Rating;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The learner's flashcard figures, on the review screen and the dashboard
 * (D22).
 */
final class FlashcardStats
{
    /**
     * A card counts as known once its interval reaches 21 days, as in Anki.
     */
    public const KNOWN_INTERVAL = 21;

    public const RETENTION_DAYS = 30;

    public const FORECAST_DAYS = 7;

    /**
     * $forecast holds the cards falling due on each of the next days; share
     * is the day's count as a percentage of the busiest day's.
     *
     * @param  list<array{date: CarbonImmutable, cards: int, share: int}>  $forecast
     */
    public function __construct(
        public readonly int $due,
        public readonly int $dueNew,
        public readonly int $pendingNew,
        public readonly int $answeredToday,
        public readonly int $learned,
        public readonly int $known,
        public readonly int $retentionReviews,
        public readonly int $retentionPassed,
        public readonly array $forecast,
    ) {}

    public static function for(User $user): self
    {
        $today = CarbonImmutable::parse($user->localNow()->toDateString());
        // "Before tomorrow": SQLite stores the date cast as "Y-m-d 00:00:00".
        $due = $user->cards()->where('due_on', '<', $today->addDay()->toDateString());
        $retention = self::retention($user);

        return new self(
            due: (clone $due)->count(),
            dueNew: (clone $due)->whereNull('last_reviewed_at')->count(),
            pendingNew: app(AddNewCards::class)->remaining($user),
            answeredToday: $user->cardReviews()->where('reviewed_at', '>=', $user->localNow()->startOfDay()->utc())->count(),
            learned: $user->cards()->whereNotNull('last_reviewed_at')->count(),
            known: $user->cards()->where('interval_days', '>=', self::KNOWN_INTERVAL)->count(),
            retentionReviews: $retention['reviews'],
            retentionPassed: $retention['passed'],
            forecast: self::forecast($user, $today),
        );
    }

    /**
     * The share of remembered answers, or null before any counted review.
     */
    public function retentionPercent(): ?float
    {
        return $this->retentionReviews === 0 ? null : $this->retentionPassed / $this->retentionReviews * 100;
    }

    /**
     * Reviews of the last 30 days whose card was remembered at its previous
     * review: the first sight of a word and the retry after Again are left
     * out. Hard, Good and Easy count as remembered.
     *
     * @return array{reviews: int, passed: int}
     */
    private static function retention(User $user): array
    {
        $reviews = $user->cardReviews()
            ->toBase()
            ->select(['rating', 'reviewed_at'])
            ->selectRaw('lag(interval_days) over (partition by card_id order by reviewed_at, id) as previous_interval');

        $row = DB::query()
            ->fromSub($reviews, 'reviews')
            ->where('reviewed_at', '>=', now()->subDays(self::RETENTION_DAYS))
            ->where('previous_interval', '>', 0)
            ->selectRaw('count(*) as reviews, sum(case when rating <> ? then 1 else 0 end) as passed', [Rating::Again->value])
            ->first();

        return ['reviews' => (int) ($row->reviews ?? 0), 'passed' => (int) ($row->passed ?? 0)];
    }

    /**
     * Cards due on each of the next seven days, not counting new words that
     * will be added on those days.
     *
     * @return list<array{date: CarbonImmutable, cards: int, share: int}>
     */
    private static function forecast(User $user, CarbonImmutable $today): array
    {
        $counts = $user->cards()
            ->where('due_on', '>=', $today->addDay()->toDateString())
            ->where('due_on', '<', $today->addDays(self::FORECAST_DAYS + 1)->toDateString())
            ->selectRaw('date(due_on) as day, count(*) as cards')
            ->groupByRaw('date(due_on)')
            ->pluck('cards', 'day');

        $busiest = max(1, (int) $counts->max());

        return array_map(function (int $offset) use ($today, $counts, $busiest) {
            $date = $today->addDays($offset);
            $cards = (int) ($counts[$date->toDateString()] ?? 0);

            return ['date' => $date, 'cards' => $cards, 'share' => (int) round($cards / $busiest * 100)];
        }, range(1, self::FORECAST_DAYS));
    }
}
