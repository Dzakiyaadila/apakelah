<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    // GET /api/applications
    public function index(Request $request)
    {
        $applications = Application::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($applications);
    }

    // POST /api/applications
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'nullable|exists:job_listings,id',
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'applied_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'type' => 'nullable|string',
            'contact' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'nullable|in:not_started,applied,interview,offer,rejected,no_reply',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $application = Application::create([
            'user_id' => $request->user()->id,
            'job_id' => $request->job_id,
            'company' => $request->company,
            'position' => $request->position,
            'applied_date' => $request->applied_date,
            'deadline' => $request->deadline,
            'type' => $request->type,
            'contact' => $request->contact,
            'location' => $request->location,
            'status' => $request->status ?? 'not_started',
        ]);

        return response()->json($application, 201);
    }

    // PATCH /api/applications/{id}
    public function update(Request $request, $id)
    {
        $application = Application::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'applied_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'type' => 'nullable|string',
            'contact' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'sometimes|in:not_started,applied,interview,offer,rejected,no_reply',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $application->update($request->only([
            'company', 'position', 'applied_date', 'deadline',
            'type', 'contact', 'location', 'status',
        ]));

        return response()->json($application);
    }

    // DELETE /api/applications/{id}
    public function destroy(Request $request, $id)
    {
        $application = Application::where('user_id', $request->user()->id)->findOrFail($id);
        $application->delete();

        return response()->json(['message' => 'deleted']);
    }
}