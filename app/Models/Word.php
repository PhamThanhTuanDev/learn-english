<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A dictionary entry imported from database/data/vocabulary.csv (D19).
 *
 * @property string $word
 * @property string $part_of_speech Empty when the source gives none.
 * @property string|null $pronunciation
 * @property string $meaning Vietnamese meaning.
 * @property string|null $definition English definition.
 * @property string|null $definition_vi
 * @property string|null $example
 * @property string|null $example_vi
 * @property string|null $level CEFR level A1–C2, or null when unknown.
 */
#[Fillable(['word', 'part_of_speech', 'pronunciation', 'meaning', 'definition', 'definition_vi', 'example', 'example_vi', 'level'])]
class Word extends Model
{
    //
}
