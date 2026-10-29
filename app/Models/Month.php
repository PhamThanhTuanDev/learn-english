<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
