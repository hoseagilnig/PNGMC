@extends('layouts.app')

@section('title', 'Applications - Admin Dashboard')

@section('content')
<div class="dashboard-container">
    <div class="page-header">
        <h1>Applications Management</h1>
        <div class="header-actions">
            <a href="{{ route('admin.applications.create') }}" class="btn btn-primary">New Application</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="{{ route('admin.applications.index') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                        <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Application #</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                <tr>
                    <td>{{ $application->application_number }}</td>
                    <td>{{ $application->first_name }} {{ $application->last_name }}</td>
                    <td>{{ $application->email ?? 'N/A' }}</td>
                    <td>{{ $application->program_interest }}</td>
                    <td>
                        <span class="badge badge-{{ str_replace('_', '-', $application->status) }}">
                            {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                        </span>
                    </td>
                    <td>{{ $application->submitted_at->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('admin.applications.show', $application->application_id) }}" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No applications found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            {{ $applications->links() }}
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

.badge-submitted { background: #ffc107; color: #000; }
.badge-under-review { background: #17a2b8; color: #fff; }
.badge-accepted { background: #28a745; color: #fff; }
.badge-rejected { background: #dc3545; color: #fff; }

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
</style>
@endpush

