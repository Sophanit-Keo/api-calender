<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_schedule_cycle_id',
        'work_date',
        'day_offset',
        'work_shift_template_id',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date:Y-m-d',
            'day_offset' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(WorkScheduleCycle::class, 'work_schedule_cycle_id');
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkShiftTemplate::class, 'work_shift_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
