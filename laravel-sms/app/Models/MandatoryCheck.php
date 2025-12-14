<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandatoryCheck extends Model
{
    use HasFactory;

    protected $primaryKey = 'check_id';
    public $incrementing = true;

    protected $fillable = [
        'application_id',
        'check_type',
        'check_name',
        'status',
        'completed_date',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_date' => 'date',
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

