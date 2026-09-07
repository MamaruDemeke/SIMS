<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\NotificationService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles Sales (recording products sold to customers).
 * The Sales Officer (role: sales) creates a sale; it is saved as PENDING.
 * The Finance role then APPROVES the sale, at which point the stock is
 * deducted (via StockService::recordMovement, which also writes a ledger entry).
 */
class SaleController extends Controller
{
    /**
     * Sales Officer: list sales with search and status filters.
     */
    public function index(Request $request)
    {
        $query = Sale::with('customer', 'creator', 'approver');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sales = $query->latest()->paginate(15)->withQueryString();

        return view('sales.index', compact('sales'));
    }

    /**
     * Sales Officer: show the "Create Sale" form.
     * Select customer → category → product → fill quantity & price.
     */
    public function create()
    {
        if (Auth::user()->role?->slug !== 'sales') {
            abort(403, 'Only the Sales Officer can create sales.');
        }

        // Active customers to choose from (inactive ones are hidden).
        $customers = Customer::where('status', true)->orderBy('name')->get();

        // Active categories + products for the JS product picker.
        $categories = \App\Models\Category::where('status', true)->orderBy('name')->get();
        $products = Product::where('status', true)->with('category')->orderBy('name')->get();

        // Convert to JSON for the JavaScript product filtering.
        $productsJson = $products->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->product_code,
            'price' => $p->selling_price,
            'category_id' => $p->category_id,
            'unit' => $p->unit,
            'type' => $p->grade,
            'diameter' => $p->diameter,
            'size' => $p->length,
            'stock' => $p->inventory?->quantity ?? 0,
        ])->values();

        return view('sales.create', compact('customers', 'categories', 'products', 'productsJson'));
    }

    /**
     * Sales Officer: create a new sale and SUBMIT it for Finance approval.
     * No stock is changed here — it stays in 'pending' until approval.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role?->slug !== 'sales') {
            abort(403, 'Only the Sales Officer can create sales.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Simple approach: pre-check stock availability for every line.
        // (Sales can be saved as pending only if the requested quantity is
        // available; finance still reviews before the stock is removed.)
        foreach ($request->items as $item) {
            $product = Product::with('inventory')->find($item['product_id']);
            $available = $product->inventory?->quantity ?? 0;
            if ($available < $item['quantity']) {
                return back()->withInput()->with(
                    'error',
                    "Insufficient stock for {$product->name}. Available: {$available}."
                );
            }
        }

        DB::beginTransaction();

        try {
            $total = 0;
            $totalQty = 0;
            foreach ($request->items as $item) {
                $total += $item['quantity'] * $item['unit_price'];
                $totalQty += $item['quantity'];
            }

            $sale = Sale::create([
                'customer_id' => $request->customer_id,
                'reference_number' => Sale::generateReferenceNumber(),
                'status' => 'pending',
                'total_amount' => $total,
                'total_quantity' => $totalQty,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'sold_at' => now(),
            ]);

            foreach ($request->items as $item) {
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'type' => $item['type'] ?? null,
                    'diameter' => $item['diameter'] ?? null,
                    'size' => $item['size'] ?? null,
                ]);
            }

            DB::commit();

            // Notify all Finance users that a new sale awaits their approval.
            NotificationService::notifyRole(
                'finance',
                'sale',
                "New sale awaiting approval",
                "Sale {$sale->reference_number} for {$sale->customer?->name} ({$totalQty} qty / ETB {$total}) is pending Finance approval.",
                null,
                null,
                $sale->id
            );

            return redirect()->route('sales.show', $sale)
                ->with('success', "Sale {$sale->reference_number} created and submitted for approval. Stock will be deducted once approved.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record sale: ' . $e->getMessage());
        }
    }

    /**
     * FINANCE: Approve a pending sale. This is the moment stock is deducted.
     */
    public function approve(Sale $sale)
    {
        if (Auth::user()->role?->slug !== 'finance') {
            abort(403, 'Only Finance can approve sales.');
        }

        if ($sale->status !== 'pending') {
            return back()->with('error', 'Only pending sales can be approved.');
        }

        DB::beginTransaction();
        try {
            $stockService = app(StockService::class);
            foreach ($sale->items as $item) {
                // Deduct stock + write a ledger movement.
                $stockService->recordMovement(
                    $item->product_id,
                    'sale',
                    $item->quantity,
                    Sale::class,
                    $sale->id,
                    "Sale {$sale->reference_number} approved — stock deducted."
                );
            }

            $sale->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            // Notify the Sales Officer who created the sale that it was approved.
            NotificationService::notifyUser(
                $sale->created_by,
                'approval',
                "Sale approved",
                "Your sale {$sale->reference_number} for {$sale->customer?->name} has been approved by Finance. Stock deducted (ETB {$sale->total_amount}).",
                null,
                null,
                $sale->id
            );

            return redirect()->route('sales.show', $sale)
                ->with('success', "Sale {$sale->reference_number} approved. Stock updated.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve sale: ' . $e->getMessage());
        }
    }

    /**
     * FINANCE: Reject a pending sale. Stock is never changed.
     */
    public function reject(Sale $sale)
    {
        if (Auth::user()->role?->slug !== 'finance') {
            abort(403, 'Only Finance can reject sales.');
        }

        if ($sale->status !== 'pending') {
            return back()->with('error', 'Only pending sales can be rejected.');
        }

        $sale->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Notify the Sales Officer who created the sale that it was rejected.
        NotificationService::notifyUser(
            $sale->created_by,
            'rejection',
            "Sale rejected",
            "Your sale {$sale->reference_number} for {$sale->customer?->name} was rejected by Finance. No stock was deducted.",
            null,
            null,
            $sale->id
        );

        return redirect()->route('sales.show', $sale)
            ->with('success', "Sale {$sale->reference_number} rejected.");
    }

    /**
     * Shows the detail page for ONE sale.
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'creator', 'approver']);

        // If the user arrived via a notification, the sale "form" (Approve /
        // Reject buttons) is on screen — clear this user's unread alerts for it.
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->where('sale_id', $sale->id)
            ->update(['is_read' => true]);

        return view('sales.show', compact('sale'));
    }
}
