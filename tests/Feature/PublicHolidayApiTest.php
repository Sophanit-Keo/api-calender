<?php

use App\Models\PublicHoliday;
use Database\Seeders\PublicHolidaySeeder;

beforeEach(function (): void {
    $this->seed(PublicHolidaySeeder::class);
});

it('lists Cambodia public national holidays for 2026', function (): void {
    $this->withHeaders(authHeaders());

    $response = $this->getJson('/api/v1/public-holidays?year=2026')
        ->assertOk()
        ->assertJsonCount(21, 'data')
        ->assertJsonPath('data.0.date', '2026-01-01')
        ->assertJsonPath('data.0.name_en', 'International New Year Day')
        ->assertJsonPath('meta.country_code', 'KH');

    $holidays = collect($response->json('data'));

    expect($holidays->firstWhere('date', '2026-05-01')['name_en'])->toContain('Visak Bochea')
        ->and($holidays->firstWhere('date', '2026-12-29')['name_en'])->toBe('Peace Day in Cambodia')
        ->and($response->json('meta.supported_years'))->toContain(2026)
        ->and(PublicHoliday::query()->where('country_code', 'KH')->count())->toBe(21);
});

it('filters Cambodia public national holidays by date and range', function (): void {
    $this->withHeaders(authHeaders());

    $this->getJson('/api/v1/public-holidays?date=2026-10-11')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name_en', 'Pchum Ben Festival')
        ->assertJsonPath('data.0.day_number', 2);

    $this->getJson('/api/v1/public-holidays?from=2026-11-23&to=2026-11-25')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name_en', 'Water Festival')
        ->assertJsonPath('data.2.day_number', 3);
});

it('returns public national holidays in calendar day and month views', function (): void {
    $this->withHeaders(authHeaders());

    $this->getJson('/api/v1/calendar/day?date=2026-12-29')
        ->assertOk()
        ->assertJsonCount(1, 'data.public_holidays')
        ->assertJsonPath('data.public_holidays.0.name_en', 'Peace Day in Cambodia');

    $response = $this->getJson('/api/v1/calendar/month?year=2026&month=11')
        ->assertOk();

    $days = collect($response->json('data.days'));
    $waterFestivalDates = $days
        ->filter(fn (array $day): bool => collect($day['public_holidays'])->contains('name_en', 'Water Festival'))
        ->pluck('calendar.date')
        ->values()
        ->all();

    expect($waterFestivalDates)->toBe(['2026-11-23', '2026-11-24', '2026-11-25']);
});
