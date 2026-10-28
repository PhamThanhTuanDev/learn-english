<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
