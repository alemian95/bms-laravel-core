<?php

namespace App\Http\Controllers;

use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\UpdateCategory;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('categories/index', [
            'categories' => $request->user()->categories()->withCount('bookmarks')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategory $action)
    {
        try {
            $action->handle($request->toData());
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created successfully']);

        return redirect()->route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategory $action)
    {
        Gate::authorize('update', $category);

        try {
            $action->handle($request->toData($category));
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated successfully']);

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category, DeleteCategory $action)
    {
        Gate::authorize('delete', $category);

        try {
            $action->handle($category);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted successfully']);

        return redirect()->route('categories.index');
    }
}
