<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DormitoryAssignment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assignment_id';
    public $incrementing = true;

    protected $fillable = [
        'student_id',
        'dormitory_id',
        'assignment_date',
        'check_in_date',
        'check_out_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assignment_date' => 'date',
            'check_in_date' => 'date',
            'check_out_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }
}

