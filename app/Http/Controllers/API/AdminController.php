<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Routes are guarded by the role middleware in routes/api.php:
 * request management and the user list are open to staff and admins,
 * creating, editing and deleting users is admin only.
 */
class AdminController extends Controller
{
    /**
     * Get all requests for admin/staff
     */
    public function allRequests(Request $request)
    {
        $requests = ServiceRequest::with(['user', 'category', 'logs.changer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Update request status (Admin/Staff)
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_review,approved,processing,completed,rejected',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found'
            ], 404);
        }

        if ($serviceRequest->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'This request was cancelled by the resident and can no longer be updated.'
            ], 422);
        }

        $oldStatus = $serviceRequest->status;
        $newStatus = $validated['status'];
        $remarks = $validated['remarks'] ?? null;

        if ($oldStatus === $newStatus && $remarks === $serviceRequest->remarks) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing to update. Choose a different status or change the remarks.'
            ], 422);
        }

        DB::transaction(function () use ($request, $serviceRequest, $oldStatus, $newStatus, $remarks) {
            $serviceRequest->status = $newStatus;
            $serviceRequest->remarks = $remarks;
            $serviceRequest->completed_at = $newStatus === 'completed'
                ? ($serviceRequest->completed_at ?? now())
                : null;
            $serviceRequest->save();

            StatusLog::create([
                'request_id' => $serviceRequest->id,
                'changed_by' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $remarks,
            ]);

            $body = $oldStatus === $newStatus
                ? "Staff added remarks to your request #{$serviceRequest->tracking_code}."
                : "Your request #{$serviceRequest->tracking_code} changed from "
                    . ServiceRequest::statusLabel($oldStatus) . ' to ' . ServiceRequest::statusLabel($newStatus) . '.';

            Notification::create([
                'user_id' => $serviceRequest->user_id,
                'request_id' => $serviceRequest->id,
                'type' => 'status_update',
                'title' => 'Request Status Updated',
                'body' => $body,
                'is_read' => false,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $serviceRequest->load(['user', 'category', 'logs.changer'])
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $byStatus = ServiceRequest::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = ['total' => ServiceRequest::count()];
        foreach (ServiceRequest::STATUSES as $status) {
            $stats[$status] = (int) ($byStatus[$status] ?? 0);
        }

        $stats['today'] = ServiceRequest::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count();
        $stats['this_week'] = ServiceRequest::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $stats['this_month'] = ServiceRequest::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        return response()->json($stats);
    }

    /**
     * List users. Admins see every account (including deactivated ones,
     * so they can be reactivated); staff only see active accounts.
     */
    public function getUsers(Request $request)
    {
        $query = User::orderBy('created_at', 'desc');

        if ($request->user()->role !== 'admin') {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    /**
     * Create a new user (Admin only)
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
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

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update an existing user (Admin only)
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string',
            'role' => 'sometimes|required|in:resident,staff,admin',
            'is_active' => 'sometimes|required|boolean',
        ]);

        $isSelf = $user->id === $request->user()->id;

        if ($isSelf && isset($validated['role']) && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own admin role.'
            ], 403);
        }

        if ($isSelf && array_key_exists('is_active', $validated) && !$validated['is_active']) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.'
            ], 403);
        }

        $user->update($validated);

        // A deactivated account is signed out everywhere
        if ($user->wasChanged('is_active') && !$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Permanently delete a user (Admin only), along with their own requests
     * and notifications. Status history they wrote on other residents'
     * requests is kept, with the author cleared.
     */
    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.'
            ], 403);
        }

        $userName = $user->name;

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "User '$userName' has been permanently deleted"
        ]);
    }
}
