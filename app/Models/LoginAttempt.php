<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks failed login attempts per email so the LoginController can
 * enforce a temporary lockout (after 3 failures) and later deactivate
 * the account (after the 4th failure).
 */
class LoginAttempt extends Model
{
    // Columns that may be mass-assigned.
    protected $fillable = ['email', 'failed_count', 'locked_until'];

    // Treat locked_until as a Carbon datetime (->locked_until->isFuture(), etc.).
    protected $casts = [
        'locked_until' => 'datetime',
    ];
}
