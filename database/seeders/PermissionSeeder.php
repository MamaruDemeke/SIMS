<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'categories',
            'products',
            'inventory',
            'inventory_movements',
            'purchases',
            'sales',
            'customers',
            'suppliers',
            'notifications',
            'users',
            'settings',
        ];

        $defaultPermissions = [
            'admin' => ['categories', 'products', 'inventory', 'inventory_movements', 'stock_alerts', 'stock_receive', 'purchases', 'sales', 'customers', 'notifications', 'users', 'settings'],
            'inventory-manager' => ['categories', 'products', 'inventory', 'inventory_movements', 'stock_alerts', 'stock_receive', 'notifications'],
            'purchase-officer' => ['purchases', 'suppliers', 'notifications'],
            'sales' => ['sales', 'customers', 'notifications'],
            'finance' => ['purchases', 'sales', 'customers', 'suppliers', 'notifications'],
        ];

        foreach ($defaultPermissions as $roleSlug => $allowedModules) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                foreach ($allowedModules as $module) {
                    Permission::create([
                        'role_id' => $role->id,
                        'module' => $module,
                    ]);
                }
            }
        }
    }
}
