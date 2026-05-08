<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminController extends Controller
{
    // ... existing methods (allRequests, updateStatus, dashboard) ...

    /**
     * Get all users (Admin and Staff can view)
     */
    public function getUsers(Request $request)
    {
        try {
            $users = User::orderBy('created_at', 'desc')->get();
            
            // Remove sensitive data for non-admin users
            if ($request->user()->role !== 'admin') {
                $users = $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                    ];
                });
            }
            
            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Get users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new user (Admin only)
     */
    public function createUser(Request $request)
    {
        try {
            // Verify admin access
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            $validated = $request->validate([
                'full_name' => 'required|string|max:150',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'role' => 'required|in:resident,staff,admin',
            ]);

            $user = User::create([
                'full_name' => $validated['full_name'],
                'name' => $validated['full_name'], // For Laravel's default name field
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'role' => $validated['role'],
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Remove password from response
            $user->makeHidden(['password']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Create user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing user (Admin only)
     */
    public function updateUser(Request $request, $id)
    {
        try {
            // Verify admin access
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            $user = User::findOrFail($id);

            // Prevent updating your own role to something lower
            if ($user->id === $request->user()->id && $request->has('role') && $request->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own admin role.'
                ], 403);
            }

            $validated = $request->validate([
                'full_name' => 'sometimes|string|max:150',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'role' => 'sometimes|in:resident,staff,admin',
            ]);

            if (isset($validated['full_name'])) {
                $user->full_name = $validated['full_name'];
                $user->name = $validated['full_name'];
            }
            if (isset($validated['phone'])) {
                $user->phone = $validated['phone'];
            }
            if (isset($validated['address'])) {
                $user->address = $validated['address'];
            }
            if (isset($validated['role'])) {
                $user->role = $validated['role'];
            }
            
            $user->save();

            // Remove password from response
            $user->makeHidden(['password']);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a user (Admin only)
     */
    public function deleteUser(Request $request, $id)
    {
        try {
            // Verify admin access
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            $user = User::findOrFail($id);

            // Prevent deleting your own account
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ], 403);
            }

            // Check if user has any requests
            $requestCount = ServiceRequest::where('user_id', $user->id)->count();
            if ($requestCount > 0) {
                // Option 1: Delete user and cascade (if foreign keys allow)
                // Option 2: Return error
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete user. They have $requestCount request(s) associated with their account."
                ], 409);
            }

            $userName = $user->full_name;
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => "User '$userName' deleted successfully"
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Delete user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
}