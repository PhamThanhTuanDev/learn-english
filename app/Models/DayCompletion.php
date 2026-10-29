<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A roadmap day the learner has finished. Create it through
 * $user->dayCompletions() so user_id is never mass assigned.
 *
 * @property int $user_id
 * @property int $roadmap_day_id
 * @property int $minutes
 * @property Carbon $completed_at Stored in UTC.
 */
#[Fillable(['roadmap_day_id', 'minutes', 'completed_at'])]
class DayCompletion extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<RoadmapDay, $this>
     */
    public function roadmapDay(): BelongsTo
    {
        return $this->belongsTo(RoadmapDay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
