<?php

use App\Models\User;

it('returns a day view with database overlays', function (): void {
    $this->withHeaders(authHeaders());

    $this->postJson('/api/v1/notes', [
        'date' => '2026-06-27',
        'text' => 'Prepare calendar API homework',
    ])->assertCreated();

    $this->postJson('/api/v1/events', [
        'title' => 'Class demo',
        'starts_at' => '2026-06-27 09:00:00',
        'ends_at' => '2026-06-27 10:00:00',
        'location' => 'IT STEP',
    ])->assertCreated();

    $this->postJson('/api/v1/holiday-events', [
        'name_en' => 'School Holiday',
        'date' => '2026-06-27',
        'type' => 'school',
    ])->assertCreated();

    $assignments = array_fill(0, 31, null);
    $assignments[1] = 'day';

    $this->putJson('/api/v1/work-schedule/cycles/2026-06-26', [
        'assignments' => $assignments,
    ])->assertOk();

    $response = $this->getJson('/api/v1/calendar/day?date=2026-06-27')
        ->assertOk()
        ->assertJsonPath('data.calendar.date', '2026-06-27')
        ->assertJsonPath('data.notes.0.text', 'Prepare calendar API homework')
        ->assertJsonPath('data.events.0.title', 'Class demo')
        ->assertJsonPath('data.holiday_events.0.name_en', 'School Holiday')
        ->assertJsonPath('data.work_shift.shift_template.code', 'day');

    expect($response->json('data.calendar.lunar_day'))->toBeBetween(1, 15);
});

it('returns a month view with all days and overlays', function (): void {
    $this->withHeaders(authHeaders());

    $this->postJson('/api/v1/notes', [
        'date' => '2026-06-05',
        'text' => 'Monthly note',
    ])->assertCreated();

    $response = $this->getJson('/api/v1/calendar/month?year=2026&month=6')
        ->assertOk()
        ->assertJsonPath('data.year', 2026)
        ->assertJsonPath('data.month', 6);

    $days = collect($response->json('data.days'));
    $juneFive = $days->firstWhere('calendar.date', '2026-06-05');

    expect($days)->toHaveCount(30)
        ->and($juneFive['notes'][0]['text'])->toBe('Monthly note');
});

it('validates calendar requests', function (): void {
    $this->withHeaders(authHeaders());

    $this->getJson('/api/v1/calendar/convert')->assertUnprocessable();
    $this->getJson('/api/v1/calendar/month?year=1899&month=13')->assertUnprocessable();
});

it('isolates calendar overlays by authenticated user', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->withHeaders(authHeaders($userA))
        ->postJson('/api/v1/notes', [
            'date' => '2026-06-27',
            'text' => 'User A note',
        ])->assertCreated();

    $this->withHeaders(authHeaders($userB))
        ->postJson('/api/v1/notes', [
            'date' => '2026-06-27',
            'text' => 'User B note',
        ])->assertCreated();

    $this->withHeaders(authHeaders($userB))
        ->postJson('/api/v1/events', [
            'title' => 'User B event',
            'starts_at' => '2026-06-27 09:00:00',
        ])->assertCreated();

    $this->withHeaders(authHeaders($userA))
        ->getJson('/api/v1/calendar/day?date=2026-06-27')
        ->assertOk()
        ->assertJsonCount(1, 'data.notes')
        ->assertJsonPath('data.notes.0.text', 'User A note')
        ->assertJsonCount(0, 'data.events');
});
