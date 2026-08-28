<?php

namespace App\Http\Controllers\Auth;
// Imports bring in the classes we use below.
use App\Http\Controllers\Controller;  // base controller every controller extends
use App\Models\User;                  // the user model, to check account status
use Illuminate\Http\Request;          // represents the incoming HTTP request
use Illuminate\Support\Facades\Auth;  // Auth facade = Laravel's login/session manager

/**
 * Handles everything related to logging in and out.
 * Routes for this are defined in routes/web.php:
 *   GET /login  → showLoginForm()
 *   POST /login → login()
 *   POST /logout→ logout()
 */
class LoginController extends Controller
{
    /**
     * Shows the login form page.
     * If the user is ALREADY logged in, don't show the form — send them to the dashboard.
     */
    public function showLoginForm()
    {
        // Auth::check() returns true if there is a logged-in user in this session.
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Otherwise render the login view (the HTML page they see).
        return view('auth.login');
    }

    /**
     * Processes the login form submission.
     * 1. Validate email + password were submitted.
     * 2. Check the email has the company domain (@yegnatrading.com).
     * 3. Try to authenticate (compare against the users table).
     * 4. On success: regenerate the session and go to the dashboard.
     * 5. On failure: go back to the login page with an error.
     */
    public function login(Request $request)
    {
        // validate(): checks the input against our rules.
        // On failure it automatically redirects back with error messages.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            // Custom error messages shown to the user in English.
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        // Get the allowed company email domain from the User model.
        $emailDomain = User::EMAIL_DOMAIN;

        // str_ends_with() checks if the email ends with "@yegnatrading.com".
        if (!str_ends_with($credentials['email'], '@' . $emailDomain)) {
            // withErrors() stores an error; back() sends the user back to the form.
            return back()->withErrors([
                'email' => 'Only @' . $emailDomain . ' email addresses are allowed.',
            ])->onlyInput('email'); // onlyInput() keeps the typed email so they don't retype it
        }

        // Check whether this account has been deactivated.
        // We look up the user by email WITHOUT logging them in yet.
        $user = User::where('email', $credentials['email'])->first();

        // If the user exists but is deactivated (is_active = false), block the login.
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Contact the administrator.',
            ])->onlyInput('email');
        }

        // Auth::attempt() checks the email+password against the DB.
        // If correct, it logs the user in (writes their ID to the session).
        // The second parameter is the "remember me" checkbox.
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Regenerate the session ID to prevent a security bug called "session fixation".
            $request->session()->regenerate();

            // redirect()->intended() sends them to the page they originally tried to visit,
            // or the dashboard if none. This is the user's homepage after login.
            return redirect()->intended(route('dashboard'));
        }

        // If we reach here, the login failed (wrong email or password).
        return back()->withErrors([
            'password' => 'The password does not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Logs the user out and clears their session.
     */
    public function logout(Request $request)
    {
        Auth::logout(); // remove the user from the session

        // invalidate() deletes all session data; regenerateToken() renews the CSRF token
        // (so old forms can't be reused after logout — a security measure).
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Send the user back to the login page.
        return redirect()->route('login');
    }
}
