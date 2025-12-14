<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_id';
    public $incrementing = true;

    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'enrollment_date',
        'graduation_date',
        'status',
        'program_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrollment_date' => 'date',
            'graduation_date' => 'date',
        ];
    }

    // Relationships
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'student_id');
    }

    public function dormitoryAssignments(): HasMany
    {
        return $this->hasMany(DormitoryAssignment::class, 'student_id');
    }

    public function advisingAppointments(): HasMany
    {
        return $this->hasMany(AdvisingAppointment::class, 'student_id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }
}

