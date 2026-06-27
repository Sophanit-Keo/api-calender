<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkScheduleCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_start_date',
        'cycle_end_date',
    ];

    protected function casts(): array
    {
        return [
            'cycle_start_date' => 'date:Y-m-d',
            'cycle_end_date' => 'date:Y-m-d',
        ];
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
    }
}
