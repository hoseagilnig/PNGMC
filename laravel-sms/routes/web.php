<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\StudentServices\DashboardController as StudentServicesDashboardController;
use App\Http\Controllers\StudentServices\SupportTicketController as StudentServicesTicketController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Applications
    Route::resource('applications', AdminApplicationController::class);
    Route::post('applications/{id}/status', [AdminApplicationController::class, 'updateStatus'])->name('applications.update-status');
    
    // Students
    Route::resource('students', AdminStudentController::class);
});

// Finance Routes
Route::middleware(['auth', 'role:finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/dashboard', [FinanceDashboardController::class, 'index'])->name('dashboard');
    
    // Invoices
    Route::resource('invoices', FinanceInvoiceController::class);
});

// Student Services Routes
Route::middleware(['auth', 'role:studentservices'])->prefix('student-services')->name('student-services.')->group(function () {
    Route::get('/dashboard', [StudentServicesDashboardController::class, 'index'])->name('dashboard');
    
    // Support Tickets
    Route::resource('tickets', StudentServicesTicketController::class);
    Route::post('tickets/{id}/status', [StudentServicesTicketController::class, 'updateStatus'])->name('tickets.update-status');
    Route::post('tickets/{id}/assign', [StudentServicesTicketController::class, 'assign'])->name('tickets.assign');
});

// HOD Routes
Route::middleware(['auth', 'role:hod'])->prefix('hod')->name('hod.')->group(function () {
    Route::get('/dashboard', function () {
        return view('hod.dashboard');
    })->name('dashboard');
});

// Public Routes
Route::get('/', function () {
    return redirect()->route('login');
});
