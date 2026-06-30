<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShiftTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'start_time',
        'end_time',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function workScheduleDays(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
