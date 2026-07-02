<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $fillable = [
        'country_code',
        'country',
        'code',
        'name_km',
        'name_en',
        'date',
        'start_date',
        'end_date',
        'type',
        'is_public',
        'is_national',
        'day_number',
        'duration_days',
        'source',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_public' => 'boolean',
            'is_national' => 'boolean',
            'day_number' => 'integer',
            'duration_days' => 'integer',
        ];
    }
}
