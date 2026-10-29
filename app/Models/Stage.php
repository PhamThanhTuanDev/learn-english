<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $number
 * @property string $title
 * @property string $goal
 * @property list<array{title: string, minutes: int, description: string}> $blocks
 * @property list<string> $notes
 */
#[Fillable(['number', 'title', 'goal', 'blocks', 'notes'])]
class Stage extends Model
{
    /**
     * @return HasMany<Month, $this>
     */
    public function months(): HasMany
    {
        return $this->hasMany(Month::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'notes' => 'array',
        ];
    }
}
