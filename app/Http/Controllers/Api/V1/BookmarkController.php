<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookmarksRequest;
use App\Http\Requests\Api\V1\StoreBookmarkRequest;
use App\Http\Resources\Api\V1\BookmarkResource;
use App\Models\Bookmark;
use App\Services\Bookmarks\BookmarkCreator;
use App\Services\Bookmarks\BookmarkLister;
use App\Services\Bookmarks\BookmarkRemover;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BookmarkController extends Controller
{
    public function index(IndexBookmarksRequest $request, BookmarkLister $lister): AnonymousResourceCollection
    {
        $result = $lister->list($request->user(), $request->toFilters());

        return BookmarkResource::collection($result['paginator']);
    }

    public function show(Bookmark $bookmark): BookmarkResource
    {
        Gate::authorize('view', $bookmark);

        return BookmarkResource::make($bookmark->load('category'));
    }

    public function store(StoreBookmarkRequest $request, BookmarkCreator $creator): BookmarkResource|JsonResponse
    {
        try {
            $bookmark = $creator->create($request->user(), $request->toData());
        } catch (DuplicateBookmarkException) {
            return response()->json(['message' => 'Bookmark already exists.'], 409);
        } catch (CategoryNotOwnedException) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected category is invalid.',
            ]);
        }

        return BookmarkResource::make($bookmark)
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Bookmark $bookmark, BookmarkRemover $remover): Response
    {
        Gate::authorize('delete', $bookmark);

        $remover->delete($bookmark);

        return response()->noContent();
    }
}
