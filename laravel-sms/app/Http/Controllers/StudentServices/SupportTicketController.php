<?php

namespace App\Http\Controllers\StudentServices;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Student;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:studentservices');
    }

    public function index(Request $request)
    {
        $query = SupportTicket::with(['student', 'submitter', 'assignee']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        // Filter by assigned to current user
        if ($request->has('my_tickets') && $request->my_tickets) {
            $query->where('assigned_to', auth()->id());
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('student-services.tickets.index', compact('tickets'));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with([
            'student',
            'submitter',
            'assignee',
            'comments.user'
        ])->findOrFail($id);

        return view('student-services.tickets.show', compact('ticket'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed,cancelled',
            'resolution_notes' => 'nullable|string',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update([
            'status' => $request->status,
            'resolution_notes' => $request->resolution_notes,
            'resolved_at' => $request->status === 'resolved' ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Ticket status updated successfully.');
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,user_id',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'in_progress',
        ]);

        return redirect()->back()->with('success', 'Ticket assigned successfully.');
    }
}

