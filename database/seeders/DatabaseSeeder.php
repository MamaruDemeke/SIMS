<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\StockNotification;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Inventory Manager', 'slug' => 'inventory-manager'],
            ['name' => 'Purchase Officer', 'slug' => 'purchase-officer'],
            ['name' => 'Sales', 'slug' => 'sales'],
            ['name' => 'Finance', 'slug' => 'finance'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@yegnatrading.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'admin')->first()->id,
        ]);

        User::create([
            'name' => 'Inventory Manager',
            'email' => 'inventory@yegnatrading.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'inventory-manager')->first()->id,
        ]);

        User::create([
            'name' => 'Purchase Officer',
            'email' => 'purchase@yegnatrading.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'purchase-officer')->first()->id,
        ]);

        User::create([
            'name' => 'Sales',
            'email' => 'sales@yegnatrading.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'sales')->first()->id,
        ]);

        User::create([
            'name' => 'Finance',
            'email' => 'finance@yegnatrading.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('slug', 'finance')->first()->id,
        ]);

        $categories = [
            ['name' => 'Rebar', 'description' => 'Steel reinforcement bars used in concrete construction', 'status' => true],
            ['name' => 'Cement', 'description' => 'Portland cement and blended cements for construction', 'status' => true],
            ['name' => 'Steel', 'description' => 'Structural steel products including beams, channels, and angles', 'status' => true],
            ['name' => 'Roofing Material', 'description' => 'Roofing sheets, tiles, and related accessories', 'status' => true],
            ['name' => 'Construction Accessories', 'description' => 'Nails, wires, bolts, fasteners, and other construction accessories', 'status' => true],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $products = [
            ['category' => 'Rebar', 'code' => 'RB-12', 'name' => '12mm Rebar', 'unit' => 'pcs', 'diameter' => '12mm', 'length' => '12m', 'grade' => 'Gr60', 'purchase' => 500, 'selling' => 600, 'min_stock' => 100, 'stock' => 500],
            ['category' => 'Rebar', 'code' => 'RB-16', 'name' => '16mm Rebar', 'unit' => 'pcs', 'diameter' => '16mm', 'length' => '12m', 'grade' => 'Gr60', 'purchase' => 800, 'selling' => 950, 'min_stock' => 100, 'stock' => 70],
            ['category' => 'Rebar', 'code' => 'RB-20', 'name' => '20mm Rebar', 'unit' => 'pcs', 'diameter' => '20mm', 'length' => '12m', 'grade' => 'Gr60', 'purchase' => 1200, 'selling' => 1400, 'min_stock' => 50, 'stock' => 0],
            ['category' => 'Cement', 'code' => 'CEM-42', 'name' => 'Habesha Cement 42.5', 'unit' => 'bag', 'purchase' => 350, 'selling' => 420, 'min_stock' => 200, 'stock' => 350],
            ['category' => 'Cement', 'code' => 'CEM-32', 'name' => 'Habesha Cement 32.5', 'unit' => 'bag', 'purchase' => 300, 'selling' => 370, 'min_stock' => 200, 'stock' => 180],
            ['category' => 'Roofing Material', 'code' => 'RF-01', 'name' => 'Roofing Sheet Corrugated', 'unit' => 'sheet', 'purchase' => 450, 'selling' => 550, 'min_stock' => 50, 'stock' => 120],
            ['category' => 'Steel', 'code' => 'ST-BM', 'name' => 'I-Beam 150mm', 'unit' => 'pcs', 'purchase' => 3500, 'selling' => 4200, 'min_stock' => 10, 'stock' => 25],
            ['category' => 'Construction Accessories', 'code' => 'CA-NL', 'name' => 'Binding Wire 1.2mm', 'unit' => 'roll', 'purchase' => 120, 'selling' => 160, 'min_stock' => 100, 'stock' => 80],
        ];

        foreach ($products as $p) {
            $cat = Category::where('name', $p['category'])->first();
            $product = Product::create([
                'category_id' => $cat->id,
                'product_code' => $p['code'],
                'name' => $p['name'],
                'unit' => $p['unit'],
                'diameter' => $p['diameter'] ?? null,
                'length' => $p['length'] ?? null,
                'grade' => $p['grade'] ?? null,
                'purchase_price' => $p['purchase'],
                'selling_price' => $p['selling'],
                'minimum_stock' => $p['min_stock'],
                'status' => true,
            ]);

            Inventory::create([
                'product_id' => $product->id,
                'quantity' => $p['stock'],
                'minimum_stock' => $p['min_stock'],
            ]);
        }

        $admin = User::where('email', 'admin@yegnatrading.com')->first();
        $sales = User::where('email', 'sales@yegnatrading.com')->first();
        $inventory = User::where('email', 'inventory@yegnatrading.com')->first();

        $movements = [
            ['product' => 'RB-12', 'type' => 'purchase', 'qty' => 600, 'note' => 'Initial purchase from supplier', 'user' => $admin],
            ['product' => 'RB-12', 'type' => 'sale', 'qty' => 100, 'note' => 'Sold to customer ABC Construction', 'user' => $sales],
            ['product' => 'RB-16', 'type' => 'purchase', 'qty' => 150, 'note' => 'Restocked from supplier', 'user' => $admin],
            ['product' => 'RB-16', 'type' => 'sale', 'qty' => 80, 'note' => 'Sold to customer Build It Ltd', 'user' => $sales],
            ['product' => 'RB-20', 'type' => 'purchase', 'qty' => 50, 'note' => 'Initial stock', 'user' => $admin],
            ['product' => 'RB-20', 'type' => 'sale', 'qty' => 50, 'note' => 'Bulk sale to Mega Construction', 'user' => $sales],
            ['product' => 'CEM-42', 'type' => 'purchase', 'qty' => 400, 'note' => 'Monthly cement order', 'user' => $admin],
            ['product' => 'CEM-42', 'type' => 'sale', 'qty' => 50, 'note' => 'Walk-in customer', 'user' => $sales],
            ['product' => 'CEM-32', 'type' => 'purchase', 'qty' => 250, 'note' => 'Monthly cement order', 'user' => $admin],
            ['product' => 'CEM-32', 'type' => 'sale', 'qty' => 70, 'note' => 'Sold to multiple customers', 'user' => $sales],
            ['product' => 'RF-01', 'type' => 'purchase', 'qty' => 150, 'note' => 'Roofing sheet order', 'user' => $admin],
            ['product' => 'RF-01', 'type' => 'sale', 'qty' => 30, 'note' => 'Sold to customer', 'user' => $sales],
            ['product' => 'ST-BM', 'type' => 'purchase', 'qty' => 30, 'note' => 'Steel beam delivery', 'user' => $admin],
            ['product' => 'ST-BM', 'type' => 'sale', 'qty' => 5, 'note' => 'Sold to contractor', 'user' => $sales],
            ['product' => 'CA-NL', 'type' => 'purchase', 'qty' => 200, 'note' => 'Wire stock', 'user' => $admin],
            ['product' => 'CA-NL', 'type' => 'sale', 'qty' => 120, 'note' => 'Multiple small sales', 'user' => $sales],
            ['product' => 'CEM-32', 'type' => 'customer_return', 'qty' => 5, 'note' => 'Customer returned damaged bags', 'user' => $sales],
            ['product' => 'RB-16', 'type' => 'adjustment', 'qty' => 0, 'note' => 'Stock count correction', 'user' => $admin],
        ];

        foreach ($movements as $m) {
            $product = Product::where('product_code', $m['product'])->first();
            if ($product) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => $m['type'],
                    'quantity' => $m['qty'],
                    'note' => $m['note'],
                    'created_by' => $m['user']->id,
                ]);
            }
        }

        $suppliers = [
            ['name' => 'Addis Ababa Construction Materials PLC', 'company_name' => 'Addis Ababa Construction Materials PLC', 'phone' => '+251911000001', 'status' => true, 'default_type' => 'Gr60', 'default_diameter' => '12mm', 'default_size' => '12m'],
            ['name' => 'Ethio Steel Import', 'company_name' => 'Ethio Steel Import', 'phone' => '+251911000002', 'status' => true, 'default_type' => 'Gr60', 'default_diameter' => '16mm', 'default_size' => '12m'],
            ['name' => 'Habesha Building Supply', 'company_name' => 'Habesha Building Supply', 'phone' => '+251911000003', 'status' => true, 'default_type' => 'Gr60', 'default_diameter' => '20mm', 'default_size' => '12m'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        $customers = [
            ['name' => 'ABC Construction', 'phone' => '+251922000001', 'status' => true],
            ['name' => 'Build It Ltd', 'phone' => '+251922000002', 'status' => true],
            ['name' => 'Mega Construction', 'phone' => '+251922000003', 'status' => true],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        // Sample purchases
        $supplier1 = Supplier::where('name', 'Addis Ababa Construction Materials PLC')->first();
        $supplier2 = Supplier::where('name', 'Ethio Steel Import')->first();
        $supplier3 = Supplier::where('name', 'Habesha Building Supply')->first();
        $purchaseOfficer = User::where('email', 'purchase@yegnatrading.com')->first();
        $finance = User::where('email', 'finance@yegnatrading.com')->first();

        // Approved purchase (full workflow completed)
        $p1 = Purchase::create([
            'supplier_id' => $supplier1->id,
            'reference_number' => 'PUR-00001',
            'receipt_number' => 'REC-00001',
            'status' => 'approved',
            'total_amount' => 115000,
            'total_quantity' => 450,
            'created_by' => $purchaseOfficer->id,
            'received_by' => $inventory->id,
            'received_at' => now()->subDays(4),
            'approved_by' => $finance->id,
            'approved_at' => now()->subDays(2),
        ]);

        PurchaseItem::create(['purchase_id' => $p1->id, 'product_id' => Product::where('product_code', 'RB-12')->first()->id, 'quantity' => 200, 'unit_cost' => 500, 'type' => 'Gr60', 'diameter' => '12mm', 'size' => '12m']);
        PurchaseItem::create(['purchase_id' => $p1->id, 'product_id' => Product::where('product_code', 'RB-16')->first()->id, 'quantity' => 100, 'unit_cost' => 800, 'type' => 'Gr60', 'diameter' => '16mm', 'size' => '12m']);
        PurchaseItem::create(['purchase_id' => $p1->id, 'product_id' => Product::where('product_code', 'CEM-42')->first()->id, 'quantity' => 150, 'unit_cost' => 350, 'type' => null, 'diameter' => null, 'size' => null]);

        // Draft purchase (just created)
        $p2 = Purchase::create([
            'supplier_id' => $supplier2->id,
            'reference_number' => 'PUR-00002',
            'status' => 'draft',
            'total_amount' => 60000,
            'total_quantity' => 50,
            'created_by' => $purchaseOfficer->id,
        ]);

        PurchaseItem::create(['purchase_id' => $p2->id, 'product_id' => Product::where('product_code', 'RB-20')->first()->id, 'quantity' => 50, 'unit_cost' => 1200, 'type' => 'Gr60', 'diameter' => '20mm', 'size' => '12m']);

        // Pending purchase (submitted, waiting for receiving)
        $p3 = Purchase::create([
            'supplier_id' => $supplier3->id,
            'reference_number' => 'PUR-00003',
            'status' => 'pending',
            'total_amount' => 82500,
            'total_quantity' => 250,
            'created_by' => $purchaseOfficer->id,
        ]);

        PurchaseItem::create(['purchase_id' => $p3->id, 'product_id' => Product::where('product_code', 'RB-12')->first()->id, 'quantity' => 100, 'unit_cost' => 500, 'type' => 'Gr60', 'diameter' => '12mm', 'size' => '12m']);
        PurchaseItem::create(['purchase_id' => $p3->id, 'product_id' => Product::where('product_code', 'CEM-32')->first()->id, 'quantity' => 150, 'unit_cost' => 300, 'type' => null, 'diameter' => null, 'size' => null]);

        // Received purchase (waiting for finance approval)
        $p4 = Purchase::create([
            'supplier_id' => $supplier1->id,
            'reference_number' => 'PUR-00004',
            'receipt_number' => 'REC-00002',
            'status' => 'received',
            'total_amount' => 42000,
            'total_quantity' => 30,
            'created_by' => $purchaseOfficer->id,
            'received_by' => $inventory->id,
            'received_at' => now()->subDay(),
        ]);

        PurchaseItem::create(['purchase_id' => $p4->id, 'product_id' => Product::where('product_code', 'ST-BM')->first()->id, 'quantity' => 30, 'unit_cost' => 3500, 'type' => null, 'diameter' => null, 'size' => null]);

        // Mark PUR-00001 notification as fulfilled (purchase completed)
        StockNotification::where('product_id', Product::where('product_code', 'RB-20')->first()->id)
            ->where('fulfilled', false)
            ->update(['fulfilled' => true, 'purchase_id' => $p1->id]);

        // Pending stock notifications (not yet fulfilled)
        $rb20 = Product::where('product_code', 'RB-20')->first();
        $cem32 = Product::where('product_code', 'CEM-32')->first();

        StockNotification::create([
            'product_id' => $rb20->id,
            'current_quantity' => 0,
            'minimum_stock' => 50,
            'type' => 'out_of_stock',
            'message' => '20mm Rebar is out of stock (0 pcs, minimum: 50). Purchase needed.',
            'notified_by' => $inventory->id,
            'purchase_id' => null,
        ]);

        StockNotification::create([
            'product_id' => $cem32->id,
            'current_quantity' => $cem32->inventory->quantity ?? 0,
            'minimum_stock' => $cem32->inventory->minimum_stock ?? 200,
            'type' => 'low_stock',
            'message' => 'Habesha Cement 32.5 is low on stock. Purchase needed.',
            'notified_by' => $inventory->id,
            'purchase_id' => null,
        ]);

        $this->call(PermissionSeeder::class);
    }
}
