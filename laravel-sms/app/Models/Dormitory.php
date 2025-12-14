<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dormitory extends Model
{
    use HasFactory;

    protected $primaryKey = 'dormitory_id';
    public $incrementing = true;

    protected $fillable = [
        'dormitory_name',
        'building_name',
        'room_number',
        'capacity',
        'current_occupancy',
        'gender_type',
        'monthly_fee',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DormitoryAssignment::class, 'dormitory_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->current_occupancy < $this->capacity;
    }
}

