<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property string $timezone
 * @property Carbon|null $roadmap_start_date
 * @property int $daily_goal_minutes
 */
#[Fillable(['name', 'email', 'password', 'timezone', 'roadmap_start_date', 'daily_goal_minutes'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roadmap_start_date' => 'date',
            'daily_goal_minutes' => 'integer',
        ];
    }

    /**
     * @return HasMany<DayCompletion, $this>
     */
    public function dayCompletions(): HasMany
    {
        return $this->hasMany(DayCompletion::class);
    }

    /**
     * @return HasMany<GateResult, $this>
     */
    public function gateResults(): HasMany
    {
        return $this->hasMany(GateResult::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /**
     * @return HasMany<CardReview, $this>
     */
    public function cardReviews(): HasMany
    {
        return $this->hasMany(CardReview::class);
    }

    /**
     * @return HasMany<Mistake, $this>
     */
    public function mistakes(): HasMany
    {
        return $this->hasMany(Mistake::class);
    }

    /**
     * The roadmap week (1–104) the learner is in today, or null before the
     * start and after the last week.
     */
    public function roadmapWeekNumber(): ?int
    {
        $day = $this->roadmapDayNumber();

        return $day === null || $day > 728 ? null : intdiv($day - 1, 7) + 1;
    }

    /**
     * Which day of the roadmap the learner is on today (day 1 = start date),
     * or null when no start date is set or it is still in the future.
     */
    public function roadmapDayNumber(): ?int
    {
        if ($this->roadmap_start_date === null) {
            return null;
        }

        // Compare plain dates so the server timezone cannot shift the result.
        $start = CarbonImmutable::parse($this->roadmap_start_date->toDateString());
        $today = CarbonImmutable::parse($this->localNow()->toDateString());
        $elapsed = (int) $start->diffInDays($today, false);

        return $elapsed < 0 ? null : $elapsed + 1;
    }

    /**
     * The calendar date of a roadmap day for this learner, or null without a
     * start date.
     */
    public function roadmapDayDate(int $number): ?CarbonImmutable
    {
        if ($this->roadmap_start_date === null) {
            return null;
        }

        return CarbonImmutable::parse($this->roadmap_start_date->toDateString())->addDays($number - 1);
    }

    /**
     * Whether the learner may tick this roadmap day: today, or an earlier day
     * they are making up. Future days stay locked.
     */
    public function hasReachedRoadmapDay(int $number): bool
    {
        $today = $this->roadmapDayNumber();

        return $today !== null && $number <= $today;
    }

    /**
     * The current time in the user's own timezone.
     *
     * Timestamps are stored in UTC; "today" in the roadmap must follow the
     * learner's clock, not the server's.
     */
    public function localNow(): Carbon
    {
        return now($this->timezone);
    }
}
