<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->orderBy('name', 'asc')->get();
        $permissions = Permission::orderBy('name', 'asc')->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'roles' => $roles,
                'permissions' => $permissions,
            ]);
        }

        return view('pages.permissions.index', compact('roles', 'permissions'));
    }

    public function sync(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permissions' => 'present|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($request->role_id);
        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Role permissions synchronized successfully.',
            'role' => $role->load('permissions'),
        ]);
    }
}
