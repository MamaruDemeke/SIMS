<?php

namespace App\Http\Middleware;

// Imports.
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware are "security guards" that run BEFORE a request reaches its controller.
 * 
 * This is the custom role middleware. It is registered with the alias 'role'
 * in bootstrap/app.php. Usage: Route::middleware('role:products')->...
 * 
 * It checks that the logged-in user's ROLE has the given MODULE permission.
 * Example: 'role:purchases' → only users whose role has the 'purchases' permission
 * can access the route group.
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param string $module  the module to check, passed as 'role:MODULE'
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // Get the logged-in user from the request (or null if not logged in).
        $user = $request->user();

        // If there's no user, or the user has no role → deny with HTTP 403 (Forbidden).
        if (!$user || !$user->role) {
            abort(403, 'Unauthorized.');
        }

        // If this user's role does NOT have the required module permission,
        // send them back to the dashboard with an error message.
        if (!$user->role->hasPermission($module)) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
        }

        // Permission granted → pass the request along to the controller.
        // $next($request) continues the request pipeline (like passing it forward).
        return $next($request);
    }
}
