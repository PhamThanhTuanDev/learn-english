<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $number Day of the roadmap, 1–728 (see User::roadmapDayNumber()).
 * @property int $week_id
 * @property int $day Day of the week, 1–7.
 * @property string $label
 * @property string $task
 * @property bool $is_rest
 */
#[Fillable(['number', 'week_id', 'day', 'label', 'task', 'is_rest'])]
class RoadmapDay extends Model
{
    /**
     * @return BelongsTo<Week, $this>
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    /**
     * @return HasMany<DayCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(DayCompletion::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_rest' => 'boolean',
        ];
    }
}
