<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $number
 * @property int $month_id
 * @property string $title
 * @property list<array{label: string, text: string, highlight: bool}> $details
 */
#[Fillable(['number', 'month_id', 'title', 'details'])]
class Week extends Model
{
    /**
     * @return BelongsTo<Month, $this>
     */
    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

    /**
     * @return HasMany<RoadmapDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(RoadmapDay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }
}
