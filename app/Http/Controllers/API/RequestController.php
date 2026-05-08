<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusLog;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        try {
            $requests = ServiceRequest::with(['category', 'logs'])
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to fetch requests'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'in:low,normal,high,urgent',
            ]);

            // Generate unique tracking code
            $trackingCode = 'BSR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Make sure tracking code is unique
            while (ServiceRequest::where('tracking_code', $trackingCode)->exists()) {
                $trackingCode = 'BSR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }

            $serviceRequest = ServiceRequest::create([
                'tracking_code' => $trackingCode,
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'normal',
                'status' => 'pending',
            ]);

            // Create status log
            StatusLog::create([
                'request_id' => $serviceRequest->id,
                'changed_by' => $request->user()->id,
                'old_status' => null,
                'new_status' => 'pending',
                'note' => 'Request submitted',
            ]);

            // Load relationships
            $serviceRequest->load(['category', 'logs']);
            
            return response()->json($serviceRequest, 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to create request'
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $serviceRequest = ServiceRequest::with(['category', 'logs.changer'])
                ->where('user_id', $request->user()->id)
                ->findOrFail($id);
            
            return response()->json($serviceRequest);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Request not found'
            ], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $serviceRequest = ServiceRequest::where('user_id', $request->user()->id)
                ->where('status', 'pending')
                ->findOrFail($id);

            $serviceRequest->update(['status' => 'cancelled']);

            StatusLog::create([
                'request_id' => $serviceRequest->id,
                'changed_by' => $request->user()->id,
                'old_status' => 'pending',
                'new_status' => 'cancelled',
                'note' => 'Cancelled by user',
            ]);

            return response()->json(['message' => 'Request cancelled successfully']);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to cancel request'
            ], 500);
        }
    }
}