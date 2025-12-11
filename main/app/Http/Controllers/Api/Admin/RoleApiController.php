<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Admin - Roles & Permissions
 *
 * Endpoints for managing roles and permissions.
 */
class RoleApiController extends Controller
{
    /**
     * List Roles
     *
     * Get all roles with their permissions.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Create Role
     *
     * Create a new role.
     *
     * @bodyParam name string required Role name. Example: Editor
     * @bodyParam permissions array Permissions array. Example: ["manage-users", "manage-content"]
     * @response 201 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    /**
     * Update Role
     *
     * Update an existing role.
     *
     * @urlParam id int required Role ID. Example: 1
     * @bodyParam name string Role name.
     * @bodyParam permissions array Permissions array.
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'permissions' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ]);
    }

    /**
     * Delete Role
     *
     * Delete a role.
     *
     * @urlParam id int required Role ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Role deleted successfully"
     * }
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
