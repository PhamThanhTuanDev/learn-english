<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A learner's flashcard for one word, scheduled with SM-2 (D20). Create it
 * through $user->cards() so user_id is never mass assigned.
 *
 * @property int $user_id
 * @property int $word_id
 * @property float $ease_factor
 * @property int $interval_days
 * @property int $repetitions Successful answers in a row since the last lapse.
 * @property int $lapses Times a learned card was forgotten.
 * @property Carbon $due_on The learner's calendar date.
 * @property Carbon|null $last_reviewed_at Stored in UTC.
 */
#[Fillable(['word_id', 'ease_factor', 'interval_days', 'repetitions', 'lapses', 'due_on', 'last_reviewed_at'])]
class Card extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Word, $this>
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * @return HasMany<CardReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CardReview::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ease_factor' => 'float',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'lapses' => 'integer',
            'due_on' => 'date',
            'last_reviewed_at' => 'datetime',
        ];
    }
}
