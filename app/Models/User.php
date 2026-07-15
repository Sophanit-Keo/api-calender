<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function ownedScheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class, 'owner_id');
    }

    public function assignedScheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class, 'assignee_id');
    }

    public function holidayEvents(): HasMany
    {
        return $this->hasMany(HolidayEvent::class);
    }

    public function workShiftTemplates(): HasMany
    {
        return $this->hasMany(WorkShiftTemplate::class);
    }

    public function workScheduleSettings(): HasMany
    {
        return $this->hasMany(WorkScheduleSetting::class);
    }

    public function workScheduleCycles(): HasMany
    {
        return $this->hasMany(WorkScheduleCycle::class);
    }

    public function createApiToken(string $name = 'api'): string
    {
        $token = Str::random(80);

        $this->apiTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $token),
        ]);

        return $token;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
