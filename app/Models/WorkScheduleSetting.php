<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'system_type',
        'remind',
        'reminder_minutes_before',
    ];

    protected function casts(): array
    {
        return [
            'system_type' => 'integer',
            'remind' => 'boolean',
            'reminder_minutes_before' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
