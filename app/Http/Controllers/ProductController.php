<?php

namespace App\Http\Controllers;

// Imports: models this controller needs.
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Handles the Product management pages (create, list, edit, delete).
 * Protected by the "role:products" middleware (only roles with the products permission).
 */
class ProductController extends Controller
{
    /**
     * Lists products with optional search / category / status filters.
     */
    public function index(Request $request)
    {
        // Query all products, pre-loading each product's category (avoids N+1 queries).
        $query = Product::with('category');

        // Apply search if provided (uses the search scope we defined on the Product model).
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by category if a category_id was chosen.
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by active/inactive status if provided.
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginate results (15 per page), keeping filters on page changes.
        $products = $query->orderBy('name')->paginate(5)->withQueryString();

        // Active categories only, used for the filter dropdown.
        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Shows the "Add Product" form.
     */
    public function create()
    {
        // Only active categories are offered, sorted by name.
        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    /**
     * Saves a new product from the submitted form.
     */
    public function store(Request $request)
    {
        // Validate every field. On failure, Laravel redirects back with errors.
        // The same product code is allowed across different grades, so the
        // uniqueness check is scoped to the product_code + grade combination.
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'product_code' => ['required', 'string', 'max:50', Rule::unique('products', 'product_code')->where('grade', $request->grade)],
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'diameter' => 'nullable|string|max:50',   // optional
            'length' => 'nullable|string|max:50',     // optional
            'grade' => 'required|string|max:50',      // required — stock is classified by grade
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        // Checkbox comes through as boolean; convert it to true/false.
        $validated['status'] = $request->boolean('status');

        // Insert the new product.
        $product = Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Shows the "Edit Product" form for one product.
     * Route-model binding fills $product from the URL id.
     */
    public function edit(Product $product)
    {
        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Updates an existing product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            // unique check ignores this product's own id, and is scoped to the
            // product_code + grade combination (same code allowed in other grades).
            'product_code' => ['required', 'string', 'max:50', Rule::unique('products', 'product_code')->where('grade', $request->grade)->ignore($product->id)],
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'diameter' => 'nullable|string|max:50',
            'length' => 'nullable|string|max:50',
            'grade' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->boolean('status');

        // Save the changes.
        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Deletes a product.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
