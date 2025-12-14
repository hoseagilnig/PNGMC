@extends('layouts.app')

@section('title', 'Invoices - Finance Dashboard')

@section('content')
<div class="dashboard-container">
    <div class="page-header">
        <h1>Invoices Management</h1>
        <div class="header-actions">
            <a href="{{ route('finance.invoices.create') }}" class="btn btn-primary">Create Invoice</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="{{ route('finance.invoices.index') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('finance.invoices.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->student->full_name ?? 'N/A' }}</td>
                    <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                    <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    <td>{{ number_format($invoice->total_amount, 2) }} PGK</td>
                    <td>{{ number_format($invoice->paid_amount, 2) }} PGK</td>
                    <td>{{ number_format($invoice->balance_amount, 2) }} PGK</td>
                    <td>
                        <span class="badge badge-{{ $invoice->status }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('finance.invoices.show', $invoice->invoice_id) }}" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No invoices found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dashboard-container {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.page-header h1 {
    color: #1d4e89;
    margin: 0;
}

.filters-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.table-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-draft { background: #6c757d; color: #fff; }
.badge-sent { background: #17a2b8; color: #fff; }
.badge-paid { background: #28a745; color: #fff; }
.badge-overdue { background: #dc3545; color: #fff; }

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: #1d4e89;
    color: white;
}

.btn-primary:hover {
    background: #163c6a;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.875rem;
}

.pagination-wrapper {
    padding: 20px;
    display: flex;
    justify-content: center;
}

.text-center {
    text-align: center;
}
</style>
@endpush

