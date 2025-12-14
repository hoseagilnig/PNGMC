<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $primaryKey = 'program_id';
    public $incrementing = true;

    protected $fillable = [
        'program_code',
        'program_name',
        'description',
        'duration_years',
        'tuition_fee',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tuition_fee' => 'decimal:2',
        ];
    }

    // Relationships
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'program_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'program_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

