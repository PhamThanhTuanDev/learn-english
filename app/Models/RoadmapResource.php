<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A learning resource referenced as R0–R29 in the roadmap texts.
 *
 * @property string $code
 * @property string $name
 * @property string $source One link, several links joined by "  ·  ", or plain text.
 * @property string $usage
 */
#[Fillable(['code', 'name', 'source', 'usage'])]
class RoadmapResource extends Model
{
    //
}
