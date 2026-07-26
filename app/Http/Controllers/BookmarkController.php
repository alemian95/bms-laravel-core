<?php

namespace App\Http\Controllers;

use App\Actions\Bookmarks\CreateBookmark;
use App\Actions\Bookmarks\DeleteBookmark;
use App\Actions\Bookmarks\UpdateBookmarkProgress;
use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Http\Requests\Bookmarks\IndexBookmarksRequest;
use App\Http\Requests\Bookmarks\StoreBookmarkRequest;
use App\Http\Requests\Bookmarks\UpdateBookmarkProgressRequest;
use App\Models\Bookmark;
use App\Models\Category;
use App\Services\Bookmarks\BookmarkLister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BookmarkController extends Controller
{
    public function index(IndexBookmarksRequest $request, BookmarkLister $lister)
    {
        $user = $request->user();
        $result = $lister->list($user, $request->toFilters());

        return Inertia::render('bookmarks/index', [
            'bookmarks' => $result['paginator'],
            'categories' => Category::where('user_id', $user->id)->orderBy('name')->get(),
            'activeCategory' => $result['activeCategory']?->slug,
            'q' => $request->filled('q') ? trim($request->string('q')->toString()) : null,
            'highlights' => $result['highlights'],
        ]);
    }

    public function store(StoreBookmarkRequest $request, CreateBookmark $action)
    {
        try {
            $action->handle($request->toData());
        } catch (CategoryNotOwnedException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Invalid category']);

            return redirect()->route('bookmarks.index');
        } catch (DuplicateBookmarkException) {
            return redirect()->route('bookmarks.index')
                ->withErrors(['url' => 'You have already saved this URL.']);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('bookmarks.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bookmark saved, extracting metadata...']);

        return redirect()->route('bookmarks.index');
    }

    public function read(Request $request, Bookmark $bookmark)
    {
        Gate::authorize('view', $bookmark);

        return Inertia::render('bookmarks/read', [
            'bookmark' => $bookmark->load('category:id,name,slug,color'),
        ]);
    }

    public function updateProgress(
        UpdateBookmarkProgressRequest $request,
        Bookmark $bookmark,
        UpdateBookmarkProgress $action,
    ) {
        Gate::authorize('update', $bookmark);

        $action->handle($request->toData($bookmark));

        return response()->noContent();
    }

    public function destroy(Request $request, Bookmark $bookmark, DeleteBookmark $action)
    {
        Gate::authorize('delete', $bookmark);

        try {
            $action->handle($bookmark);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('bookmarks.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bookmark deleted successfully']);

        return redirect()->route('bookmarks.index');
    }
}
