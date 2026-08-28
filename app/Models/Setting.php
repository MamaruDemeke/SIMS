<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores simple application-wide settings as key/value pairs.
 * For example the company logo path is stored as key = 'company_logo'.
 */
class Setting extends Model
{
    // Which columns may be filled via mass assignment (e.g. Setting::create([...])).
    protected $fillable = ['key', 'value'];

    /**
     * Fetches a setting's value by key (or the given default if it doesn't exist).
     * This is the main way other code reads a setting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Creates or updates a setting by key.
     * (Used e.g. when the admin uploads a new company logo.)
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
