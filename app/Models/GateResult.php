<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The learner's result for a month's end-of-month gate. Create it through
 * $user->gateResults() so user_id is never mass assigned.
 *
 * @property int $user_id
 * @property int $month_id
 * @property bool $passed
 * @property string|null $note
 * @property Carbon $checked_at Stored in UTC.
 */
#[Fillable(['month_id', 'passed', 'note', 'checked_at'])]
class GateResult extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Month, $this>
     */
    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }
}
