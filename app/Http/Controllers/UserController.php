<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AppRoute;
use App\Models\UserAccess;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    // Display all users
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::all(),
            'app_routes' => AppRoute::all(),
            'user_accesses' => UserAccess::all(),
        ]);
    }

    // Store new user
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    // Update existing user
    public function update(Request $request, string $id)
    {
        Log::info('update');
        Log::info($request->all());

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'role' => 'required|in:0,1,2,3,4,5,6,7,8',
            'password' => 'nullable|string|min:6',
            'route_ids' => 'nullable|array',
            'route_ids.*' => 'integer|exists:app_routes,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        $routeIds = $validated['route_ids'] ?? [];

        // save user_accesses via pivot table
        $user->accessibleRoutes()->sync($routeIds);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function getUserAccesses(Request $request)
    {
        $userAccesses = UserAccess::with('appRoute')->where('user_id', auth()->id())->get();

        return response()->json([
            'user_accesses' => $userAccesses,
        ]);
    }

    public function update_orig(Request $request, string $id)
    {
        Log::info('update');
        Log::info($request->all());
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'role'  => 'required|in:0,1,2,3,4,5,6,7',          // validate role
            'password' => 'nullable|string|min:6',   // optional password
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $request->role;

        // Only update password if provided
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->back()->with('success', 'User updated successfully.');
    }


    // Delete user
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
