<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:finance');
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['student', 'creator']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('invoice_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('invoice_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('student_number', 'like', "%{$search}%")
                         ->orWhere('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(20);

        return view('finance.invoices.index', compact('invoices'));
    }

    public function show($id)
    {
        $invoice = Invoice::with(['student', 'creator', 'items', 'payments'])->findOrFail($id);
        return view('finance.invoices.show', compact('invoice'));
    }

    public function create()
    {
        $students = Student::active()->orderBy('last_name')->get();
        return view('finance.invoices.create', compact('students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT);

        // Calculate totals
        $totalAmount = 0;
        foreach ($request->items as $item) {
            $totalAmount += $item['quantity'] * $item['unit_price'];
        }

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'student_id' => $request->student_id,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        // Create invoice items
        foreach ($request->items as $item) {
            $invoice->items()->create([
                'item_description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
                'item_type' => $item['item_type'] ?? 'other',
            ]);
        }

        return redirect()->route('finance.invoices.show', $invoice->invoice_id)
            ->with('success', 'Invoice created successfully.');
    }
}

