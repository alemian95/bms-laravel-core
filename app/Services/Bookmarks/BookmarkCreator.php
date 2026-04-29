<?php

namespace App\Services\Bookmarks;

use App\Data\Bookmarks\CreateBookmarkData;
use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

class BookmarkCreator
{
    public function __construct(
        private BookmarkUrlNormalizer $normalizer,
    ) {}

    /**
     * @throws CategoryNotOwnedException
     * @throws DuplicateBookmarkException
     */
    public function create(User $user, CreateBookmarkData $data): Bookmark
    {
        $url = $this->normalizer->normalize($data->url);

        if ($data->categoryId !== null) {
            $owns = $user->categories()->whereKey($data->categoryId)->exists();
            if (! $owns) {
                throw new CategoryNotOwnedException($data->categoryId);
            }
        }

        if ($user->bookmarks()->where('url', $url)->exists()) {
            throw new DuplicateBookmarkException($url);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'category_id' => $data->categoryId,
            'url' => $url,
            'status' => 'pending',
        ]);

        Bus::chain([
            new ExtractBookmarkMetadataJob($bookmark),
            new ParseArticleContentJob($bookmark),
        ])->dispatch();

        return $bookmark;
    }
}
