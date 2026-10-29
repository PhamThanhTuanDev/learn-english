<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $number
 * @property int $stage_id
 * @property int $first_week
 * @property int $last_week
 * @property string $focus
 * @property string $gate
 */
#[Fillable(['number', 'stage_id', 'first_week', 'last_week', 'focus', 'gate'])]
class Month extends Model
{
    /**
     * @return BelongsTo<Stage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /**
     * @return HasMany<Week, $this>
     */
    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class);
    }

    /**
     * @return HasManyThrough<RoadmapDay, Week, $this>
     */
    public function days(): HasManyThrough
    {
        return $this->hasManyThrough(RoadmapDay::class, Week::class);
    }

    /**
     * @return HasMany<GateResult, $this>
     */
    public function gateResults(): HasMany
    {
        return $this->hasMany(GateResult::class);
    }

    /**
     * The roadmap day the end-of-month gate opens on: day 1 of the month's
     * last week, so a gate taken a few days early can still be recorded.
     */
    public function gateOpensOnDay(): int
    {
        return ($this->last_week - 1) * 7 + 1;
    }
}
