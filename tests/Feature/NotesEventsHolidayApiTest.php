<?php

use App\Models\User;

it('creates lists updates and deletes notes', function (): void {
    $this->withHeaders(authHeaders());

    $id = $this->postJson('/api/v1/notes', [
        'date' => '2026-06-27',
        'text' => 'First note',
    ])->assertCreated()->json('data.id');

    $this->getJson('/api/v1/notes?date=2026-06-27')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.text', 'First note');

    $this->patchJson("/api/v1/notes/$id", [
        'text' => 'Updated note',
    ])->assertOk()->assertJsonPath('data.text', 'Updated note');

    $this->deleteJson("/api/v1/notes/$id")->assertNoContent();
    $this->getJson('/api/v1/notes?date=2026-06-27')->assertJsonCount(0, 'data');
});

it('creates lists updates and deletes events', function (): void {
    $this->withHeaders(authHeaders());

    $id = $this->postJson('/api/v1/events', [
        'title' => 'Morning class',
        'starts_at' => '2026-06-27 08:00:00',
        'ends_at' => '2026-06-27 11:00:00',
        'all_day' => false,
        'color' => '#1f7a8c',
        'reminder_minutes_before' => 30,
    ])->assertCreated()->json('data.id');

    $this->getJson('/api/v1/events?date=2026-06-27')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Morning class');

    $this->patchJson("/api/v1/events/$id", [
        'location' => 'Room 12',
    ])->assertOk()->assertJsonPath('data.location', 'Room 12');

    $this->deleteJson("/api/v1/events/$id")->assertNoContent();
    $this->getJson('/api/v1/events?date=2026-06-27')->assertJsonCount(0, 'data');
});

it('creates lists updates and deletes holiday events including yearly recurrence', function (): void {
    $this->withHeaders(authHeaders());

    $id = $this->postJson('/api/v1/holiday-events', [
        'name_km' => 'ថ្ងៃឈប់សម្រាកសាលា',
        'name_en' => 'School Break',
        'date' => '2026-11-09',
        'type' => 'school',
        'is_recurring_yearly' => true,
    ])->assertCreated()->json('data.id');

    $this->getJson('/api/v1/holiday-events?date=2027-11-09')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name_en', 'School Break');

    $this->patchJson("/api/v1/holiday-events/$id", [
        'notes' => 'No class',
    ])->assertOk()->assertJsonPath('data.notes', 'No class');

    $this->deleteJson("/api/v1/holiday-events/$id")->assertNoContent();
    $this->getJson('/api/v1/holiday-events?date=2027-11-09')->assertJsonCount(0, 'data');
});

it('returns validation errors for invalid CRUD payloads', function (): void {
    $this->withHeaders(authHeaders());

    $this->postJson('/api/v1/notes', [
        'date' => '2026-06-27',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/events', [
        'title' => 'Broken event',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/holiday-events', [
        'date' => '2026-06-27',
    ])->assertUnprocessable();
});

it('isolates notes events and holiday events by authenticated user', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $noteA = $this->withHeaders(authHeaders($userA))
        ->postJson('/api/v1/notes', [
            'date' => '2026-06-27',
            'text' => 'User A note',
        ])->assertCreated()->json('data.id');

    $noteB = $this->withHeaders(authHeaders($userB))
        ->postJson('/api/v1/notes', [
            'date' => '2026-06-27',
            'text' => 'User B note',
        ])->assertCreated()->json('data.id');

    $eventB = $this->withHeaders(authHeaders($userB))
        ->postJson('/api/v1/events', [
            'title' => 'User B event',
            'starts_at' => '2026-06-27 08:00:00',
        ])->assertCreated()->json('data.id');

    $holidayB = $this->withHeaders(authHeaders($userB))
        ->postJson('/api/v1/holiday-events', [
            'name_en' => 'User B holiday',
            'date' => '2026-06-27',
        ])->assertCreated()->json('data.id');

    $this->withHeaders(authHeaders($userA))
        ->getJson('/api/v1/notes?date=2026-06-27')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $noteA)
        ->assertJsonPath('data.0.text', 'User A note');

    $this->withHeaders(authHeaders($userA))
        ->getJson("/api/v1/notes/$noteB")
        ->assertNotFound();

    $this->withHeaders(authHeaders($userA))
        ->patchJson("/api/v1/events/$eventB", ['location' => 'Leaked room'])
        ->assertNotFound();

    $this->withHeaders(authHeaders($userA))
        ->deleteJson("/api/v1/holiday-events/$holidayB")
        ->assertNotFound();
});
