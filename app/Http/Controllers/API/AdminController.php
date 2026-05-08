<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusLog;
use App\Models\Notification;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminController extends Controller
{
    /**
     * Get all requests for admin/staff
     */
    public function allRequests(Request $request)
    {
        try {
            $user = $request->user();
            $query = ServiceRequest::with(['user', 'category', 'logs.changer']);
            
            // Staff can see all requests, admin sees all
            $requests = $query->orderBy('created_at', 'desc')->get();
            
            // Return as JSON array directly
            return response()->json($requests);
            
        } catch (\Exception $e) {
            Log::error('All requests error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch requests'
            ], 500);
        }
    }

    /**
     * Update request status (Admin/Staff)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in_review,approved,processing,completed,rejected',
                'remarks' => 'nullable|string',
            ]);

            $serviceRequest = ServiceRequest::findOrFail($id);
            $oldStatus = $serviceRequest->status;
            $newStatus = $request->status;

            // Update the request
            $serviceRequest->status = $newStatus;
            if ($request->remarks) {
                $serviceRequest->remarks = $request->remarks;
            }
            if ($newStatus === 'completed') {
                $serviceRequest->completed_at = now();
            }
            $serviceRequest->save();

            // Create status log
            StatusLog::create([
                'request_id' => $serviceRequest->id,
                'changed_by' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $request->remarks,
            ]);

            // Create notification for the user
            Notification::create([
                'user_id' => $serviceRequest->user_id,
                'request_id' => $serviceRequest->id,
                'type' => 'status_update',
                'title' => 'Request Status Updated',
                'body' => "Your request #{$serviceRequest->tracking_code} status changed from " . ucfirst($oldStatus) . " to " . ucfirst($newStatus),
                'is_read' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $serviceRequest->load(['user', 'category', 'logs'])
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Update status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        try {
            $today = now()->startOfDay();
            
            $stats = [
                'total' => ServiceRequest::count(),
                'pending' => ServiceRequest::where('status', 'pending')->count(),
                'in_review' => ServiceRequest::where('status', 'in_review')->count(),
                'approved' => ServiceRequest::where('status', 'approved')->count(),
                'processing' => ServiceRequest::where('status', 'processing')->count(),
                'completed' => ServiceRequest::where('status', 'completed')->count(),
                'rejected' => ServiceRequest::where('status', 'rejected')->count(),
                'today' => ServiceRequest::whereDate('created_at', $today)->count(),
                'this_week' => ServiceRequest::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => ServiceRequest::whereMonth('created_at', now()->month)->count(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data'
            ], 500);
        }
    }

    /**
     * Get all active users (Admin and Staff can view)
     * Fixed: Only returns active users (not deactivated)
     */
    public function getUsers(Request $request)
    {
        try {
            // FIXED: Only get users that are active (not deactivated)
            $users = User::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Remove sensitive data for non-admin users
            if ($request->user()->role !== 'admin') {
                $users = $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
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
     * Get all users including deactivated (Admin only)
     */
    public function getAllUsers(Request $request)
    {
        try {
            // Verify admin access
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }

            $users = User::withTrashed()
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Get all users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users'
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
                'name' => 'required|string|max:150',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'role' => 'required|in:resident,staff,admin',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'role' => $validated['role'],
                'is_active' => true,
            ]);

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
                'name' => 'sometimes|string|max:150',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'role' => 'sometimes|in:resident,staff,admin',
                'is_active' => 'sometimes|boolean',
            ]);

            $updates = [];
            if (isset($validated['name'])) $updates['name'] = $validated['name'];
            if (isset($validated['phone'])) $updates['phone'] = $validated['phone'];
            if (isset($validated['address'])) $updates['address'] = $validated['address'];
            if (isset($validated['role'])) $updates['role'] = $validated['role'];
            if (isset($validated['is_active'])) $updates['is_active'] = $validated['is_active'];
            
            $user->update($updates);

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
     * Delete a user (Admin only) - HARD DELETE
     * This permanently removes the user from the database
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

            $userName = $user->name;

            // FIXED: HARD DELETE - permanently remove from database
            // This will cascade delete all related requests, notifications, and logs
            $user->forceDelete();

            return response()->json([
                'success' => true,
                'message' => "User '$userName' has been permanently deleted"
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

    /**
     * Reactivate a deactivated user (Admin only)
     */
    public function reactivateUser(Request $request, $id)
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
            
            $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => "User '{$user->name}' has been reactivated"
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Reactivate user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate user'
            ], 500);
        }
    }
}