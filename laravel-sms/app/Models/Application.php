<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $primaryKey = 'application_id';
    public $incrementing = true;

    protected $fillable = [
        'application_number',
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
        'grade_12_passed',
        'maths_grade',
        'physics_grade',
        'english_grade',
        'overall_gpa',
        'program_interest',
        'expression_date',
        'status',
        'assessed_by',
        'assessment_date',
        'assessment_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'expression_date' => 'date',
            'assessment_date' => 'date',
            'grade_12_passed' => 'boolean',
            'overall_gpa' => 'decimal:2',
        ];
    }

    // Relationships
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function hodDecisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hod_decision_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function previousStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'previous_student_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    public function mandatoryChecks(): HasMany
    {
        return $this->hasMany(MandatoryCheck::class, 'application_id');
    }

    public function correspondence(): HasMany
    {
        return $this->hasMany(Correspondence::class, 'application_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class, 'application_id');
    }

    public function continuingRequirements(): HasMany
    {
        return $this->hasMany(ContinuingStudentRequirement::class, 'application_id');
    }
}

