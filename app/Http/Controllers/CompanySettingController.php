<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Handles application-wide settings, currently the company logo.
 *
 * Endpoints (both require login + the 'settings' permission):
 *   GET  /settings/logo → show()      (the logo settings page)
 *   POST /settings/logo → updateLogo() (upload a new logo)
 */
class CompanySettingController extends Controller
{
    /**
     * Shows the Company Settings page (where the admin can change the logo).
     * Reads the current stored logo path, if any.
     */
    public function show()
    {
        // The current logo path (or null if none has been uploaded yet).
        $logoPath = Setting::get('company_logo');

        return view('settings.logo', compact('logoPath'));
    }

    /**
     * Handles the logo upload.
     * Saves the uploaded image into storage/public/logos and records its path.
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ]);

        // Delete the old logo file (if any) to avoid orphaned files.
        $oldPath = Setting::get('company_logo');
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        // Save the new image as a random filename (prevents overwriting/caching issues).
        $path = $request->file('logo')->store('logos', 'public');

        // Remember the new path in the settings table.
        Setting::set('company_logo', $path);

        return redirect()->route('settings.logo')
            ->with('success', 'Company logo updated successfully.');
    }
}
