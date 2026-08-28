<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Handles Category management (create, list, edit, delete).
 * Protected by the "role:categories" middleware.
 */
class CategoryController extends Controller
{
    /**
     * Lists categories with optional search / status filters.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search by name OR description.
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by active/inactive if chosen.
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginate, keep filters on page change.
        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    /**
     * Shows the "Add Category" form.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Saves a new category.
     */
    public function store(Request $request)
    {
        // name must be unique (no two categories with the same name).
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->boolean('status');

        Category::create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Shows the "Edit Category" form.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Updates an existing category.
     */
    public function update(Request $request, Category $category)
    {
        // Unique check ignores this category's own name.
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->boolean('status');

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Deletes a category, but ONLY if it has no products attached.
     * (Prevents breaking products that still reference this category.)
     */
    public function destroy(Category $category)
    {
        // If the category has any products, refuse to delete it.
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing products.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
