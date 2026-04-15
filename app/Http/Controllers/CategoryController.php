<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('categories/index', [
            'categories' => Category::withCount('bookmarks')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('categories/form', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //
        }
        catch (\Exception $e) {
            return redirect()->route('categories.form')->with('error', $e->getMessage());
        }
        return redirect()->route('categories.index')->with('success', 'Category created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return Inertia::render('categories/show', [
            'category' => $category
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return Inertia::render('categories/form', [
            'category' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        try {
            //
        }
        catch (\Exception $e) {
            return redirect()->route('categories.form', [
                'category' => $category
            ])->with('error', $e->getMessage());
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
        }
        catch (\Exception $e) {
            return redirect()->route('categories.index')->with('error', $e->getMessage());
        }
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully');
    }
}
