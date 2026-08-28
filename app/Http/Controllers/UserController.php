<?php

namespace App\Http\Controllers;

// Imports: models and helpers this controller uses.
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Hash::make() securely hashes passwords

/**
 * Handles the User (staff account) management pages — create, list, edit, delete.
 * Protected by the "role:users" middleware in routes/web.php (admin-only).
 * This is a full "resource" controller covering:
 *   GET   /users          → index()  (list)
 *   GET   /users/create   → create() (show add form)
 *   POST  /users          → store()  (save new user)
 *   GET   /users/{id}/edit→ edit()   (show edit form)
 *   PUT   /users/{id}     → update() (save changes)
 *   DELETE/users/{id}     → destroy()(delete user)
 */
class UserController extends Controller
{
    /**
     * Shows the list of users with optional search + role filters.
     */
    public function index(Request $request)
    {
        // Start querying all users, pre-loading each user's role
        // (with('role') avoids N+1 queries — it fetches all roles in one query).
        $query = User::with('role');

        // Only apply the search filter if the user typed something.
        if ($request->filled('search')) {
            $search = $request->search;
            // where(name LIKE ... OR email LIKE ...)
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Optional filter by role.
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // paginate(15) shows 15 users per page. withQueryString() keeps the
        // current filters when paging (so the search stays applied).
        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        // All roles, needed for the filter dropdown.
        $roles = Role::orderBy('name')->get();

        // Render the index view, passing $users and $roles to it.
        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Shows the "Add User" form.
     */
    public function create()
    {
        // We need the list of roles so the form can offer a dropdown.
        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Saves a new user from the submitted form.
     */
    public function store(Request $request)
    {
        // Validate the input. If it fails, Laravel redirects back with errors.
        $validated = $request->validate([
            'name' => 'required|string|max:255',   // required, text, max 255 chars
            'email' => 'required|email|max:255|unique:users,email', // must be unique in users table
            'password' => 'required|string|min:8|confirmed', // min 8 chars, must match confirmation field
            'role_id' => 'required|exists:roles,id', // must be a real role id
            'is_active' => 'boolean', // optional checkbox: active (true) or deactivated (false)
        ]);

        // Never store the raw password! Hash it first (one-way encryption).
        $validated['password'] = Hash::make($validated['password']);

        // Convert the checkbox to a real boolean. Defaults to TRUE (active)
        // so a brand-new account starts active unless the box was unchecked.
        $validated['is_active'] = $request->boolean('is_active', true);

        // Insert the new user into the database.
        User::create($validated);

        // Redirect to the list with a green success flash message.
        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Shows the "Edit User" form for one user.
     * Laravel route-model binding auto-fills $user from the URL id.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Updates an existing user's details.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // 'unique:users,email,' . $user->id  → ignore this user's own email when checking uniqueness
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            // Password is optional here ("nullable") because we keep the old one if blank.
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean', // optional checkbox for active/deactivated
        ]);

        // Only re-hash & update the password if the admin typed a new one.
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // remove it so the old password stays
        }

        // Convert the checkbox to a real boolean.
        $validated['is_active'] = $request->boolean('is_active');

        // Save the changes to the database.
        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Quick toggle: activate / deactivate a user without opening the edit form.
     * Used by the Active/Deactivate button on the Users list.
     */
    public function toggleActive(User $user)
    {
        // Flip the current value: active → deactivated, deactivated → active.
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User $user->name was $status.");
    }

    /**
     * Deletes a user.
     */
    public function destroy(User $user)
    {
        $user->delete(); // remove the row from the users table

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
