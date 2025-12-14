<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisingAppointment extends Model
{
    use HasFactory;

    protected $primaryKey = 'appointment_id';
    public $incrementing = true;

    protected $fillable = [
        'student_id',
        'advisor_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'subject',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'datetime',
        ];
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }
}

