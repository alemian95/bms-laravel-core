<?php

namespace BmsCore\Packages\Ai\Models;

use App\Models\Bookmark;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;

/**
 * Un turno della conversazione su un singolo bookmark.
 *
 * @property int $id
 * @property int $bookmark_id
 * @property MessageRole $role
 * @property string $content
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable([
    'bookmark_id',
    'role',
    'content',
])]
class AiChatMessage extends Model
{
    protected $table = 'ai_chat_messages';

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForBookmark(Builder $query, Bookmark $bookmark): Builder
    {
        return $query->where('bookmark_id', $bookmark->id);
    }

    /**
     * @return BelongsTo<Bookmark, $this>
     */
    public function bookmark(): BelongsTo
    {
        return $this->belongsTo(Bookmark::class);
    }

    /**
     * Rappresentazione attesa dall'AI SDK per la history della conversazione.
     */
    public function toAiMessage(): Message
    {
        return new Message($this->role, $this->content);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => MessageRole::class];
    }
}
