<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Categories\CategoryCreator;
use App\Services\Categories\CategoryRemover;
use App\Services\Categories\CategoryUpdater;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('categories/index', [
            'categories' => Category::withCount('bookmarks')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request, CategoryCreator $creator)
    {
        try {
            $creator->create($request->user(), $request->toData());
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created successfully']);

        return redirect()->route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category, CategoryUpdater $updater)
    {
        try {
            $updater->update($category, $request->toData());
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated successfully']);

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category, CategoryRemover $remover)
    {
        try {
            $remover->delete($category);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted successfully']);

        return redirect()->route('categories.index');
    }
}
