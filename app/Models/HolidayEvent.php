<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_km',
        'name_en',
        'date',
        'end_date',
        'type',
        'source',
        'is_fixed',
        'is_recurring_yearly',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_fixed' => 'boolean',
            'is_recurring_yearly' => 'boolean',
        ];
    }
}
