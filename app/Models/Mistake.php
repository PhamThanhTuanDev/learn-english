<?php

namespace App\Models;

use App\Enums\MistakeCategory;
use Database\Factories\MistakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line of the learner's mistake log (D23). Create it through
 * $user->mistakes() so user_id is never mass assigned.
 *
 * @property int $user_id
 * @property string $wrong The sentence or phrase as the learner got it wrong.
 * @property string $correct
 * @property MistakeCategory $category
 * @property string|null $note
 * @property int|null $week Roadmap week 1–104.
 * @property int $occurrences How many times the mistake has been made.
 * @property Carbon|null $last_repeated_at Stored in UTC.
 * @property Carbon $created_at
 */
#[Fillable(['wrong', 'correct', 'category', 'note', 'week', 'occurrences'])]
class Mistake extends Model
{
    /** @use HasFactory<MistakeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => MistakeCategory::class,
            'week' => 'integer',
            'occurrences' => 'integer',
            'last_repeated_at' => 'datetime',
        ];
    }
}
