<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @group Admin - Admin Users
 *
 * Endpoints for managing admin users.
 */
class AdminUserApiController extends Controller
{
    /**
     * List Admin Users
     *
     * Get all admin users.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function index()
    {
        $admins = Admin::with('role')->get();

        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

    /**
     * Create Admin User
     *
     * Create a new admin user.
     *
     * @bodyParam name string required Admin name. Example: John Doe
     * @bodyParam email string required Admin email. Example: admin@example.com
     * @bodyParam password string required Password. Example: password123
     * @bodyParam role_id int Role ID. Example: 1
     * @response 201 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully',
            'data' => $admin
        ], 201);
    }

    /**
     * Update Admin User
     *
     * Update an admin user.
     *
     * @urlParam id int required Admin ID. Example: 1
     * @bodyParam name string Admin name.
     * @bodyParam email string Admin email.
     * @bodyParam password string New password.
     * @bodyParam role_id int Role ID.
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:admins,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'role_id']);
        
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Admin updated successfully',
            'data' => $admin
        ]);
    }

    /**
     * Delete Admin User
     *
     * Delete an admin user.
     *
     * @urlParam id int required Admin ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Admin deleted successfully"
     * }
     */
    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin deleted successfully'
        ]);
    }
}
