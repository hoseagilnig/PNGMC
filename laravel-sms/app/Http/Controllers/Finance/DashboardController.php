<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:finance');
    }

    public function index()
    {
        // Get finance dashboard statistics
        $stats = [
            'total_invoices' => \App\Models\Invoice::count(),
            'pending_invoices' => \App\Models\Invoice::where('status', 'sent')->count(),
            'overdue_invoices' => \App\Models\Invoice::where('status', 'overdue')->count(),
            'total_revenue' => \App\Models\Payment::sum('amount'),
        ];

        return view('finance.dashboard', compact('stats'));
    }
}

