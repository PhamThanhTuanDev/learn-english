<?php

namespace App\Models;

use App\Enums\MistakeCategory;
use Database\Factories\MistakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property Carbon|null $pinned_at Set while it is one of the priorities.
 * @property Carbon $created_at
 */
#[Fillable(['wrong', 'correct', 'category', 'note', 'week', 'occurrences'])]
class Mistake extends Model
{
    /** @use HasFactory<MistakeFactory> */
    use HasFactory;

    /**
     * At most three mistakes are pinned as the priorities to fix (D24).
     */
    public const MAX_PINNED = 3;

    /**
     * A mistake made this many times is a priority (appendix A).
     */
    public const PRIORITY_OCCURRENCES = 3;

    /**
     * The pinned priorities, in the order they were pinned.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pinned(Builder $query): void
    {
        $query->whereNotNull('pinned_at')->orderBy('pinned_at')->orderBy('id');
    }

    /**
     * Unpinned mistakes made at least three times: most often first, then
     * the most recently made.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function suggested(Builder $query): void
    {
        $query->whereNull('pinned_at')
            ->where('occurrences', '>=', self::PRIORITY_OCCURRENCES)
            ->orderByDesc('occurrences')
            ->orderByRaw('coalesce(last_repeated_at, created_at) desc')
            ->orderByDesc('id');
    }

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
            'pinned_at' => 'datetime',
        ];
    }
}
