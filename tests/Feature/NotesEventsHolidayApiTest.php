<?php

it('creates lists updates and deletes notes', function (): void {
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
