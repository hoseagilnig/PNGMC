<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Program;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index(Request $request)
    {
        $query = Student::with('program');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by program
        if ($request->has('program_id') && $request->program_id) {
            $query->where('program_id', $request->program_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate(20);
        $programs = Program::active()->get();

        return view('admin.students.index', compact('students', 'programs'));
    }

    public function show($id)
    {
        $student = Student::with([
            'program',
            'enrollments',
            'invoices',
            'payments',
            'supportTickets',
            'dormitoryAssignments',
            'advisingAppointments'
        ])->findOrFail($id);

        return view('admin.students.show', compact('student'));
    }

    public function create()
    {
        $programs = Program::active()->get();
        return view('admin.students.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_number' => 'required|unique:students,student_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'program_id' => 'nullable|exists:programs,program_id',
            'enrollment_date' => 'nullable|date',
        ]);

        Student::create($request->all());

        return redirect()->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $programs = Program::active()->get();
        return view('admin.students.edit', compact('student', 'programs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'student_number' => 'required|unique:students,student_number,' . $id . ',student_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'program_id' => 'nullable|exists:programs,program_id',
            'status' => 'required|in:active,inactive,graduated,suspended,withdrawn',
        ]);

        $student = Student::findOrFail($id);
        $student->update($request->all());

        return redirect()->route('admin.students.show', $id)
            ->with('success', 'Student updated successfully.');
    }
}

