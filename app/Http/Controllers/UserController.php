<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->orderBy('name', 'asc')->get();
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($users);
        }
        $roles = Role::orderBy('name')->get();
        $clients = Client::where('is_active', true)->orderBy('name')->get();

        return view('pages.users.index', compact('users', 'roles', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|string|exists:roles,name',
        ]);

        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
        ]);

        if (! empty($request->password)) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        if (! empty($request->role)) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->load('roles'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own user account.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
