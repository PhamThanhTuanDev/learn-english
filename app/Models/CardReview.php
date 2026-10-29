<?php

namespace App\Models;

use App\Enums\Rating;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One answer to a flashcard and the schedule it produced. Create it through
 * $user->cardReviews() so user_id is never mass assigned.
 *
 * @property int $user_id
 * @property int $card_id
 * @property Rating $rating
 * @property float $ease_factor
 * @property int $interval_days
 * @property Carbon $due_on
 * @property Carbon $reviewed_at Stored in UTC.
 */
#[Fillable(['card_id', 'rating', 'ease_factor', 'interval_days', 'due_on', 'reviewed_at'])]
class CardReview extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => Rating::class,
            'ease_factor' => 'float',
            'interval_days' => 'integer',
            'due_on' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }
}
