<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Role;
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

        $this->call(PermissionSeeder::class);
    }
}
