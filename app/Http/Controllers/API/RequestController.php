<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\StatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = ServiceRequest::with(['category', 'logs.changer'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $serviceRequest = DB::transaction(function () use ($request, $validated) {
            $serviceRequest = ServiceRequest::create([
                'tracking_code' => $this->generateTrackingCode(),
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority' => $validated['priority'] ?? 'normal',
                'status' => 'pending',
            ]);

            StatusLog::create([
                'request_id' => $serviceRequest->id,
                'changed_by' => $request->user()->id,
                'old_status' => null,
                'new_status' => 'pending',
                'note' => 'Request submitted',
            ]);

            return $serviceRequest;
        });

        return response()->json($serviceRequest->load(['category', 'logs.changer']), 201);
    }

    public function show(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::with(['category', 'logs.changer'])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$serviceRequest) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        return response()->json($serviceRequest);
    }

    public function destroy(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::where('user_id', $request->user()->id)->find($id);

        if (!$serviceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found'
            ], 404);
        }

        if (!$serviceRequest->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be cancelled.',
                'current_status' => $serviceRequest->status
            ], 422);
        }

        DB::transaction(function () use ($request, $serviceRequest) {
            $serviceRequest->update(['status' => 'cancelled']);

            StatusLog::create([
                'request_id' => $serviceRequest->id,
                'changed_by' => $request->user()->id,
                'old_status' => 'pending',
                'new_status' => 'cancelled',
                'note' => 'Cancelled by resident',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Request cancelled successfully',
            'data' => $serviceRequest
        ]);
    }

    private function generateTrackingCode(): string
    {
        do {
            $code = 'BSR-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (ServiceRequest::where('tracking_code', $code)->exists());

        return $code;
    }
}
