<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Global helper: returns the public URL of the company logo if one has been
 * uploaded, otherwise null. The sidebar and login page use this to display the logo.
 *
 * Usage in Blade: <img src="{{ company_logo() }}" />
 *   - returns null when no logo is set, so views can fall back to a default.
 */
if (!function_exists('company_logo')) {
    function company_logo(): ?string
    {
        $path = Setting::get('company_logo');

        if (!$path) {
            return null;
        }

        // Storage::url() turns a stored path like "logos/abc.png" into "/storage/logos/abc.png".
        return Storage::disk('public')->url($path);
    }
}
