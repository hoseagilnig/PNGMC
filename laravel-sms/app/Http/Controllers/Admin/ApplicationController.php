<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index(Request $request)
    {
        $query = Application::with(['assessor', 'hodDecisionBy', 'student']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by application type
        if ($request->has('type') && $request->type) {
            $query->where('application_type', $request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('submitted_at', 'desc')->paginate(20);

        return view('admin.applications.index', compact('applications'));
    }

    public function show($id)
    {
        $application = Application::with([
            'assessor',
            'hodDecisionBy',
            'student',
            'invoice',
            'documents',
            'mandatoryChecks',
            'correspondence',
            'notes',
            'continuingRequirements'
        ])->findOrFail($id);

        return view('admin.applications.show', compact('application'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:submitted,under_review,hod_review,accepted,rejected,correspondence_sent,checks_pending,checks_completed,enrolled,ineligible',
            'notes' => 'nullable|string',
        ]);

        $application = Application::findOrFail($id);
        $application->update([
            'status' => $request->status,
            'assessed_by' => auth()->id(),
            'assessment_date' => now(),
            'assessment_notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }
}

