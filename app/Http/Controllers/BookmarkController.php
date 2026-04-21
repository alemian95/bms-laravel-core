<?php

namespace App\Http\Controllers;

use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $bookmarks = Bookmark::query()
            ->where('user_id', $userId)
            ->with('category:id,name,slug,color')
            ->when($request->string('category')->toString(), function ($query, string $slug) use ($userId) {
                $query->whereHas('category', function ($q) use ($slug, $userId) {
                    $q->where('user_id', $userId)->where('slug', $slug);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(9);

        return Inertia::render('bookmarks/index', [
            'bookmarks' => $bookmarks,
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(),
            'activeCategory' => $request->string('category')->toString() ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url:http,https|max:2048',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $userId = $request->user()->id;
        $normalizedUrl = $this->normalizeUrl($validated['url']);

        if (! empty($validated['category_id'])) {
            $categoryBelongsToUser = Category::where('id', $validated['category_id'])
                ->where('user_id', $userId)
                ->exists();

            if (! $categoryBelongsToUser) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'Invalid category']);

                return redirect()->route('bookmarks.index');
            }
        }

        $alreadyExists = Bookmark::where('user_id', $userId)
            ->where('url', $normalizedUrl)
            ->exists();

        if ($alreadyExists) {
            return redirect()->route('bookmarks.index')
                ->withErrors(['url' => 'You have already saved this URL.']);
        }

        try {
            $bookmark = Bookmark::create([
                'user_id' => $userId,
                'category_id' => $validated['category_id'] ?? null,
                'url' => $normalizedUrl,
                'status' => 'pending',
            ]);

            Bus::chain([
                new ExtractBookmarkMetadataJob($bookmark),
                new ParseArticleContentJob($bookmark),
            ])->dispatch();
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

    public function updateProgress(Request $request, Bookmark $bookmark)
    {
        Gate::authorize('update', $bookmark);
        $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);
        $bookmark->update([
            'scroll_position' => $request->progress,
            'reading_progress' => max($request->progress, $bookmark->reading_progress),
        ]);

        return response()->noContent();
    }

    public function destroy(Request $request, Bookmark $bookmark)
    {
        Gate::authorize('delete', $bookmark);

        try {
            $bookmark->delete();
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('bookmarks.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bookmark deleted successfully']);

        return redirect()->route('bookmarks.index');
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $hashPos = strpos($url, '#');

        return $hashPos === false ? $url : substr($url, 0, $hashPos);
    }
}
