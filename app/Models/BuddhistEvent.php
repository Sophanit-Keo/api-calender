<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuddhistEvent extends Model
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
        'tradition',
        'is_public_holiday',
        'lunar_month_name',
        'lunar_day',
        'is_waxing',
        'buddhist_era',
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
            'is_public_holiday' => 'boolean',
            'is_waxing' => 'boolean',
            'lunar_day' => 'integer',
            'buddhist_era' => 'integer',
            'day_number' => 'integer',
            'duration_days' => 'integer',
        ];
    }
}
