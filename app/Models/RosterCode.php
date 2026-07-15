<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RosterCode extends Model
{
    use HasFactory;

    public const CATEGORIES = ['shift', 'leave'];

    protected $fillable = [
        'code',
        'label',
        'category',
        'start_time',
        'end_time',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function rosterEntries(): HasMany
    {
        return $this->hasMany(RosterEntry::class);
    }
}
