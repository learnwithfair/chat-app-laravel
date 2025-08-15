<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     * GET /api/users
     */
    public function index()
    {
        // Return a paginated list of users
        return User::paginate(15);
    }

    /**
     * Store a newly created user in storage.
     * POST /api/users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     * GET /api/users/{user}
     */
    public function show(User $user)
    {
        // Laravel's route model binding automatically finds the user or returns a 404
        return $user;
    }

    /**
     * Update the specified user in storage.
     * PUT /api/users/{user}
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id), // Ignore the current user's email
            ],
            'password' => ['sometimes', 'nullable', 'confirmed', Rules\Password::defaults()],
        ]);
        
        // Update the user with validated data
        $user->update($validated);
        
        // Hash password if it's being updated
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     * DELETE /api/users/{user}
     */
    public function destroy(User $user)
    {
        $user->delete();

        // Return a 204 No Content response, which is standard for a successful deletion
        return response()->noContent();
    }
}