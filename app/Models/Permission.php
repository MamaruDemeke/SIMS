<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a single permission record in the permissions table.
 * A permission is simply: "this role (role_id) is allowed to access this module".
 * Example: role_id=1, module='products'  → the Admin role can access Products.
 */
class Permission extends Model
{
    // Which columns can be mass-filled.
    protected $fillable = ['role_id', 'module'];

    /**
     * Relationship: a Permission belongs to one Role.
     * Usage: $permission->role -> the Role this permission belongs to.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
