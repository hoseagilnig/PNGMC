<?php

namespace App\Http\Controllers\StudentServices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:studentservices');
    }

    public function index()
    {
        // Get student services dashboard statistics
        $stats = [
            'total_students' => \App\Models\Student::count(),
            'active_students' => \App\Models\Student::where('status', 'active')->count(),
            'open_tickets' => \App\Models\SupportTicket::where('status', 'open')->count(),
            'pending_appointments' => \App\Models\AdvisingAppointment::where('status', 'scheduled')->count(),
        ];

        return view('student-services.dashboard', compact('stats'));
    }
}

