<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;

/**
 * Handles the Stock Movements (history/ledger) page.
 * This is a read-only screen showing the audit trail of all stock changes.
 * Protected by the "role:inventory_movements" middleware.
 */
class InventoryMovementController extends Controller
{
    /**
     * Lists stock movements with filters for search, type, and date range.
     */
    public function index(Request $request)
    {
        // Query all movements, pre-loading the product and the user who made the move.
        // The linked document (reference) is loaded per type: purchases bring
        // their supplier, sales bring their customer.
        $query = InventoryMovement::with('product', 'creator')
            ->with(['reference' => fn ($r) => $r->morphWith([
                Purchase::class => ['supplier'],
                Sale::class => ['customer'],
            ])]);

        // Search by product name or code (filter on the related product).
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Filter by movement type (purchase, sale, return, adjustment...).
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by start date (created_at >= date_from).
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter by end date (created_at <= date_to).
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Show newest movements first; either all on one page (?all=1) or 6 per page.
        $query->orderBy('created_at', 'desc');
        $showAll = $request->boolean('all');
        $movements = $showAll
            ? $query->get()
            : $query->paginate(6)->withQueryString();

        // Record that this user just viewed the movements page, so the sidebar
        // badge ("new movements") no longer counts them as new going forward.
        $user = auth()->user();
        $user->forceFill(['movements_viewed_at' => now()])->save();

        return view('inventory.movements', compact('movements'));
    }

    /**
     * Admin-only: delete the selected inventory movements.
     * Non-admin requests are rejected with a 403 before anything is deleted.
     */
    public function deleteSelected(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $ids = $this->validatedIds($request);

        if (empty($ids)) {
            return back()->with('error', 'No movements selected.');
        }

        InventoryMovement::whereIn('id', $ids)->delete();

        return redirect()->route('inventory.movements')
            ->with('success', count($ids) . ' movement(s) deleted.');
    }

    /**
     * Pulls a clean list of movement ids from the request (numbers only).
     */
    private function validatedIds(Request $request): array
    {
        return collect($request->input('movement_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
