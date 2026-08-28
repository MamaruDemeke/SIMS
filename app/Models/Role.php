<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a "role" (a job title / access level) in the roles table.
 * Examples: Administrator, Purchase Officer, Finance, Inventory Manager.
 * A role groups together many permissions that determine what the user can do.
 */
class Role extends Model
{
    // Which columns are allowed to be mass-filled (via create/update arrays).
    protected $fillable = ['name', 'slug'];

    /**
     * Relationship: one Role has many Users.
     * Usage: $role->users  -> collection of User objects holding this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relationship: one Role has many Permission records.
     * Usage: $role->permissions -> collection of Permission objects.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Checks whether this role has been granted the given module permission.
     * Example: hasPermission('products') → true if a Permission row exists
     * where role_id = this role AND module = 'products'.
     */
    public function hasPermission(string $module): bool
    {
        // ->exists() returns true/false without fetching full rows.
        return $this->permissions()->where('module', $module)->exists();
    }
}
