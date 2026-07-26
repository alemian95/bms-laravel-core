<?php

namespace BmsCore\Packages\Ai\Models;

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bookmark_id
 * @property string $summary
 */
#[Fillable([
    'bookmark_id',
    'summary',
])]
class AiSummary extends Model
{
    protected $table = 'ai_summaries';

    /**
     * @return BelongsTo<Bookmark, $this>
     */
    public function bookmark(): BelongsTo
    {
        return $this->belongsTo(Bookmark::class);
    }
}
