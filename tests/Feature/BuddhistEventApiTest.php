<?php

use App\Models\BuddhistEvent;
use Database\Seeders\BuddhistEventSeeder;

beforeEach(function (): void {
    $this->seed(BuddhistEventSeeder::class);
});

it('lists Cambodia Buddhist events for supported years', function (): void {
    $this->withHeaders(authHeaders());

    $response = $this->getJson('/api/v1/buddhist-events?year=2026')
        ->assertOk()
        ->assertJsonPath('meta.country_code', 'KH')
        ->assertJsonPath('meta.supported_years', [2020, 2021, 2022, 2023, 2024, 2025, 2026]);

    $events = collect($response->json('data'));

    expect($events->count())->toBeGreaterThan(50)
        ->and($events->contains('name_en', 'Visak Bochea Day'))->toBeTrue()
        ->and($events->contains('type', 'uposatha'))->toBeTrue()
        ->and(BuddhistEvent::query()->where('country_code', 'KH')->count())->toBeGreaterThan(350);
});

it('filters Buddhist events by date range and type', function (): void {
    $this->withHeaders(authHeaders());

    $this->getJson('/api/v1/buddhist-events?date=2026-05-01')
        ->assertOk()
        ->assertJsonFragment(['name_en' => 'Visak Bochea Day'])
        ->assertJsonFragment(['is_public_holiday' => true]);

    $uposatha = $this->getJson('/api/v1/buddhist-events?from=2026-05-01&to=2026-05-31&type=uposatha')
        ->assertOk()
        ->json('data');

    expect($uposatha)->not->toBeEmpty()
        ->and(collect($uposatha)->pluck('type')->unique()->values()->all())->toBe(['uposatha']);
});

it('returns Buddhist events in calendar day and month views', function (): void {
    $this->withHeaders(authHeaders());

    $this->getJson('/api/v1/calendar/day?date=2026-05-01')
        ->assertOk()
        ->assertJsonFragment(['name_en' => 'Visak Bochea Day']);

    $response = $this->getJson('/api/v1/calendar/month?year=2026&month=5')
        ->assertOk();

    $days = collect($response->json('data.days'));
    $daysWithBuddhistEvents = $days
        ->filter(fn (array $day): bool => count($day['buddhist_events']) > 0)
        ->pluck('calendar.date')
        ->values();

    expect($daysWithBuddhistEvents)->toContain('2026-05-01');
});
