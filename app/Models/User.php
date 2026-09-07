<?php

namespace App\Models;

// Imports: these bring in the classes/features this User model uses.
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable; // Base class that gives login/auth abilities
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rules\Email;

/**
 * Represents one row in the "users" table.
 * A Model is how your PHP code talks to the database. Each property (like $user->name)
 * maps to a column in the table.
 */

// #[Fillable] = which columns are allowed to be filled with data (protects against mass assignment).
#[Fillable(['name', 'email', 'password', 'role_id', 'is_active', 'movements_viewed_at'])]
// #[Hidden] = these fields are hidden whenever the user's data is converted to JSON (e.g. never send password to the browser).
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // HasFactory = lets tests/seeder create fake users. Notifiable = ability to send notifications.
    use HasFactory, Notifiable;

    // A constant (never changes) containing the only email domain allowed to log in.
    // Used in the login controller to reject non-company emails.
    public const EMAIL_DOMAIN = 'yegnatrading.com';

    /**
     * Relationship: a User belongs to one Role.
     * The foreign key is "role_id" on the users table.
     * Usage: $user->role  -> returns the Role object (or null).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Helper: checks if this user's role "slug" matches the given one.
     * A slug is a url-friendly name like "purchase-officer".
     * Usage: $user->hasRole('finance')
     */
    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug; // "?" is null-safe: if no role, returns null (falsy)
    }

    /**
     * Defines how certain columns are formatted/typed when read from the DB.
     * - email_verified_at → a datetime object
     * - password → automatically HASHED whenever set (security: never stored as plain text)
     * - settings → stored as JSON in DB, returned as an array (used for display preferences)
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'is_active' => 'boolean', // true = active, false = deactivated
            'movements_viewed_at' => 'datetime',
        ];
    }
}
