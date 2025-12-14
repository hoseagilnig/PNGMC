<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correspondence extends Model
{
    use HasFactory;

    protected $primaryKey = 'correspondence_id';
    public $incrementing = true;

    protected $fillable = [
        'application_id',
        'correspondence_type',
        'subject',
        'message',
        'sent_by',
        'sent_date',
        'sent_at',
        'attachment_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sent_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}

