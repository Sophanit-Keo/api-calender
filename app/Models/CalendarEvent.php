<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'location',
        'color',
        'reminder_minutes_before',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'reminder_minutes_before' => 'integer',
        ];
    }
}
