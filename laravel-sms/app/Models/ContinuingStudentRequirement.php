<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContinuingStudentRequirement extends Model
{
    use HasFactory;

    protected $primaryKey = 'requirement_id';
    public $incrementing = true;

    protected $fillable = [
        'application_id',
        'requirement_type',
        'requirement_name',
        'status',
        'verified_by',
        'verified_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'verified_date' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

