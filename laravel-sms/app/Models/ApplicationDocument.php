<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $primaryKey = 'document_id';
    public $incrementing = true;

    protected $fillable = [
        'application_id',
        'document_type',
        'document_name',
        'file_path',
        'uploaded_at',
        'verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
            'verified' => 'boolean',
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

