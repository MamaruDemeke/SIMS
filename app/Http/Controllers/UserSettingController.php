<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles per-user display settings (dark mode, font size, eye comfort, brightness).
 * These are stored on the logged-in user's "settings" column (as JSON) in the DB,
 * so the preferences follow the user on any computer they log in from.
 *
 * Endpoints (both must be logged in):
 *   GET  /user/settings  → get()  (returns current settings as JSON)
 *   POST /user/settings  → save() (saves settings sent as JSON)
 */
class UserSettingController extends Controller
{
    /**
     * Returns the current user's saved settings as JSON.
     * Used by the JavaScript in the layout to restore the user's theme/font etc.
     */
    public function get()
    {
        // The "settings" column is cast to an array, so it returns a JSON object.
        return response()->json(Auth::user()->settings ?? []);
    }

    /**
     * Saves the settings sent by the browser (as JSON) to the user's record.
     */
    public function save(Request $request)
    {
        $user = Auth::user();

        // Store the entire request body as the settings value.
        // (Stored as JSON thanks to the 'settings' => 'array' cast.)
        $user->settings = $request->all();
        $user->save();

        return response()->json(['success' => true]);
    }
}
