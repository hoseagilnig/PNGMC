<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';
    public $incrementing = true;

    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'email',
        'phone',
        'role',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    // Laravel Auth compatibility - map password_hash to password
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Set password using Laravel's Hash
    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = Hash::make($value);
    }

    // Check if user has specific role
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    // Check if user has any of the given roles
    public function hasAnyRole(array $roles)
    {
        return in_array($this->role, $roles);
    }

    // Check if user is active
    public function isActive()
    {
        return $this->status === 'active';
    }

    // Relationships
    public function createdInvoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function processedPayments()
    {
        return $this->hasMany(Payment::class, 'processed_by');
    }

    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function submittedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'submitted_by');
    }
}

