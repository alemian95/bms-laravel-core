<?php

namespace App\Models;

use Database\Factories\BookmarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

#[Fillable([
    'user_id',
    'category_id',
    'url',
    'title',
    'domain',
    'author',
    'thumbnail_url',
    'content_html',
    'content_text',
    'reading_progress',
    'scroll_position',
    'status',
])]
class Bookmark extends Model
{
    /** @use HasFactory<BookmarkFactory> */
    use HasFactory;

    use Searchable;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function searchableAs(): string
    {
        return 'bookmarks';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'parsed' && filled($this->content_text);
    }

    /**
     * @return array{id: int, user_id: int, category_id: int|null, title: string|null, author: string|null, domain: string|null, category_name: string|null, content_text: string|null, status: string, created_at: int}
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('category');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'author' => $this->author,
            'domain' => $this->domain,
            'category_name' => $this->category?->name,
            'content_text' => $this->content_text,
            'status' => $this->status,
            'created_at' => $this->created_at?->getTimestamp(),
        ];
    }
}
