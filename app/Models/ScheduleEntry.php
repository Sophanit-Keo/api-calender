<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEntry extends Model
{
    use HasFactory;

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'owner_id',
        'assignee_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'task',
        'description',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date:Y-m-d'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
