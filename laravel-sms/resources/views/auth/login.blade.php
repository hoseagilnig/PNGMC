@extends('layouts.app')

@section('title', 'Login - PNG Maritime College SMS')

@section('content')
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <img src="{{ asset('images/pnmc.png') }}" alt="PNG Maritime College" class="login-logo">
            <h1>PNG Maritime College</h1>
            <p class="login-subtitle">Student Management System</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf

            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control @error('username') is-invalid @enderror" 
                    value="{{ old('username') }}" 
                    required 
                    autofocus
                >
                @error('username')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control @error('password') is-invalid @enderror" 
                    required
                >
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="usertype">User Type</label>
                <select 
                    id="usertype" 
                    name="usertype" 
                    class="form-control @error('usertype') is-invalid @enderror" 
                    required
                >
                    <option value="">Select User Type</option>
                    <option value="admin" {{ old('usertype') == 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="finance" {{ old('usertype') == 'finance' ? 'selected' : '' }}>Finance</option>
                    <option value="studentservices" {{ old('usertype') == 'studentservices' ? 'selected' : '' }}>Student Services</option>
                    <option value="hod" {{ old('usertype') == 'hod' ? 'selected' : '' }}>Head of Department</option>
                </select>
                @error('usertype')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Remember Me
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Login
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; {{ date('Y') }} PNG Maritime College. All rights reserved.</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 50%, #0f2a4a 100%);
    padding: 20px;
}

.login-box {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    padding: 40px;
    max-width: 450px;
    width: 100%;
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-logo {
    max-width: 150px;
    margin-bottom: 20px;
}

.login-header h1 {
    color: #1d4e89;
    margin-bottom: 5px;
}

.login-subtitle {
    color: #666;
    font-size: 0.9rem;
}

.login-form .form-group {
    margin-bottom: 20px;
}

.login-form label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 600;
}

.login-form .form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.login-form .form-control:focus {
    outline: none;
    border-color: #1d4e89;
    box-shadow: 0 0 0 3px rgba(29, 78, 137, 0.1);
}

.btn-primary {
    background: #1d4e89;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
}

.btn-primary:hover {
    background: #163c6a;
}

.login-footer {
    text-align: center;
    margin-top: 20px;
    color: #666;
    font-size: 0.9rem;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>
@endpush

