@extends('layouts.app')

@section('title', 'Admin Dashboard - PNG Maritime College SMS')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome, {{ auth()->user()->full_name }}</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['total_students']) }}</h3>
                <p>Total Students</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['active_students']) }}</h3>
                <p>Active Students</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['total_applications']) }}</h3>
                <p>Total Applications</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3>{{ number_format($stats['pending_applications']) }}</h3>
                <p>Pending Applications</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header h1 {
    color: #1d4e89;
    margin-bottom: 5px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 3rem;
}

.stat-content h3 {
    font-size: 2rem;
    color: #1d4e89;
    margin: 0;
}

.stat-content p {
    color: #666;
    margin: 5px 0 0 0;
}
</style>
@endpush

