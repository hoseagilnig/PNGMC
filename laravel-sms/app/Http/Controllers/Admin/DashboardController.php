<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        // Get dashboard statistics
        $stats = [
            'total_students' => \App\Models\Student::count(),
            'active_students' => \App\Models\Student::where('status', 'active')->count(),
            'total_applications' => \App\Models\Application::count(),
            'pending_applications' => \App\Models\Application::where('status', 'submitted')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

