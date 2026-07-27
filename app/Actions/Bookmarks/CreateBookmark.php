<?php

namespace App\Actions\Bookmarks;

use App\Actions\Action;
use App\Data\Bookmarks\CreateBookmarkData;
use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Services\Bookmarks\BookmarkUrlNormalizer;
use Illuminate\Support\Facades\Bus;

/**
 * @implements Action<CreateBookmarkData, Bookmark>
 */
final class CreateBookmark implements Action
{
    public function __construct(
        private BookmarkUrlNormalizer $normalizer,
    ) {}

    /**
     * @param  CreateBookmarkData  $input
     *
     * @throws CategoryNotOwnedException
     * @throws DuplicateBookmarkException
     */
    public function handle(mixed $input): Bookmark
    {
        $user = $input->user;
        $url = $this->normalizer->normalize($input->url);

        if ($input->categoryId !== null) {
            $owns = $user->categories()->whereKey($input->categoryId)->exists();
            if (! $owns) {
                throw new CategoryNotOwnedException($input->categoryId);
            }
        }

        if ($user->bookmarks()->where('url', $url)->exists()) {
            throw new DuplicateBookmarkException($url);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'category_id' => $input->categoryId,
            'url' => $url,
            'status' => 'pending',
        ]);

        $chain = [
            new ExtractBookmarkMetadataJob($bookmark),
            new ParseArticleContentJob($bookmark),
        ];

        Bus::chain($chain)->dispatch();

        return $bookmark;
    }
}
