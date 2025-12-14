<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'usertype' => 'required|in:admin,finance,studentservices,hod',
        ]);

        $credentials = [
            'username' => $request->username,
            'password_hash' => $request->password, // Will be checked manually
            'status' => 'active',
        ];

        // Custom authentication logic to work with password_hash field
        $user = \App\Models\User::where('username', $request->username)
            ->where('status', 'active')
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'username' => ['Username not found or account is inactive.'],
            ]);
        }

        // Check role matches
        if ($user->role !== $request->usertype) {
            throw ValidationException::withMessages([
                'username' => ['Username and role do not match.'],
            ]);
        }

        // Verify password
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['Password and username do not match.'],
            ]);
        }

        // Login user
        Auth::login($user, $request->filled('remember'));

        // Update last login
        $user->update(['last_login' => now()]);

        // Redirect based on role
        return $this->redirectToRole($user->role);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    protected function redirectToRole($role)
    {
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'finance' => redirect()->route('finance.dashboard'),
            'studentservices' => redirect()->route('student-services.dashboard'),
            'hod' => redirect()->route('hod.dashboard'),
            default => redirect()->route('login'),
        };
    }
}

