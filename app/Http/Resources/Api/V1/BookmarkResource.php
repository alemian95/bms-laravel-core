<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bookmark
 */
class BookmarkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'domain' => $this->domain,
            'author' => $this->author,
            'thumbnail_url' => $this->thumbnail_url,
            'status' => $this->status,
            'reading_progress' => $this->reading_progress,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
