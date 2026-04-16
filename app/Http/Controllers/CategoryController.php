<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('categories/index', [
            'categories' => Category::withCount('bookmarks')->orderBy('name')->get(),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    //    /**
    //     * Show the form for creating a new resource.
    //     */
    //    public function create()
    //    {
    //        return Inertia::render('categories/form', []);
    //    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7'
        ]);

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $count = 2;

        while (Category::where('user_id', $request->user()->id)->where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        try {
            Category::create([...$validated, 'slug' => $slug, 'user_id' => $request->user()->id]);
        } catch (\Exception $e) {
            return redirect()->route('categories.index')->with('error', $e->getMessage());
        }

        return redirect()->route('categories.index')->with('success', 'Category created successfully');
    }

    //    /**
    //     * Display the specified resource.
    //     */
    //    public function show(Category $category)
    //    {
    //        return Inertia::render('categories/show', [
    //            'category' => $category
    //        ]);
    //    }

    //    /**
    //     * Show the form for editing the specified resource.
    //     */
    //    public function edit(Category $category)
    //    {
    //        return Inertia::render('categories/form', [
    //            'category' => $category
    //        ]);
    //    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $count = 2;

        while (Category::where('user_id', $request->user()->id)
            ->where('slug', $slug)
            ->where('id', '!=', $category->id)
            ->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        try {
            $category->update([...$validated, 'slug' => $slug]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('categories.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            $category->delete();
        } catch (\Exception $e) {
            return redirect()->route('categories.index')->with('error', $e->getMessage());
        }

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully');
    }
}
