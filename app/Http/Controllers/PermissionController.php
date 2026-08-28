<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

/**
 * Handles the Permissions management page (admin settings).
 * Here you assign each role which modules it can access.
 * Protected by the "role:settings" middleware.
 */
class PermissionController extends Controller
{
    /**
     * Shows the permissions matrix: a big table of roles × modules with checkboxes.
     */
    public function index()
    {
        // All roles, each with its current permissions pre-loaded.
        $roles = Role::with('permissions')->get();

        // The list of modules (features) that can be granted to roles.
        $modules = [
            'categories' => 'Categories',
            'products' => 'Products',
            'inventory' => 'Stock (Inventory)',
            'inventory_movements' => 'Stock Movements',
            'stock_alerts' => 'Stock Alerts',
            'stock_receive' => 'Awaiting Receive',
            'purchases' => 'Purchases',
            'sales' => 'Sales',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'notifications' => 'Notifications',
            'users' => 'Users',
            'settings' => 'Settings',
        ];

        return view('permissions.index', compact('roles', 'modules'));
    }

    /**
     * Saves the permission matrix.
     * Simplest reset approach: delete ALL permission rows, then recreate them
     * from what was submitted on the form.
     */
    public function save(Request $request)
    {
        // The submitted permissions: permissions[roleId][] = moduleName
        $allPermissions = $request->input('permissions', []);

        // Wipe the whole table first (fresh start for each save).
        Permission::query()->delete();

        // Loop over each role that had boxes checked.
        foreach ($allPermissions as $roleId => $modules) {
            $role = Role::find($roleId);
            if (!$role) continue; // skip if the role doesn't exist

            // Create one Permission row for each module checked for this role.
            foreach ($modules as $module) {
                Permission::create([
                    'role_id' => $role->id,
                    'module' => $module,
                ]);
            }
        }

        return back()->with('success', 'All permissions saved successfully.');
    }
}
