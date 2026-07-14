<?php

use App\Models\ScheduleEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('serves the schedule dashboard', function (): void {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('ScheduleFlow')
        ->assertSee('Schedule sheet');
});

it('creates filters updates and deletes schedule entries', function (): void {
    $owner = User::factory()->create();
    $assignee = User::factory()->create(['name' => 'Assigned Teammate']);
    $headers = authHeaders($owner);

    $id = $this->withHeaders($headers)->postJson('/api/v1/schedules', [
        'scheduled_date' => '2026-07-20',
        'start_time' => '09:00',
        'end_time' => '10:30',
        'task' => 'Prepare launch report',
        'description' => 'Collect the final metrics.',
        'priority' => 'high',
        'status' => 'scheduled',
        'assignee_id' => $assignee->id,
    ])->assertCreated()
        ->assertJsonPath('data.task', 'Prepare launch report')
        ->assertJsonPath('data.assignee.id', $assignee->id)
        ->json('data.id');

    $this->withHeaders($headers)
        ->getJson('/api/v1/schedules?search=launch&priority=high&user_id='.$assignee->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $id);

    $this->withHeaders($headers)
        ->patchJson("/api/v1/schedules/{$id}", [
            'scheduled_date' => '2026-07-21',
            'status' => 'in_progress',
        ])->assertOk()
        ->assertJsonPath('data.scheduled_date', '2026-07-21')
        ->assertJsonPath('data.status', 'in_progress');

    $this->withHeaders($headers)
        ->deleteJson("/api/v1/schedules/{$id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('schedule_entries', ['id' => $id]);
});

it('shares assigned schedules while isolating unrelated users', function (): void {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $outsider = User::factory()->create();
    $entry = ScheduleEntry::query()->create([
        'owner_id' => $owner->id,
        'assignee_id' => $assignee->id,
        'scheduled_date' => '2026-07-20',
        'task' => 'Shared team task',
        'priority' => 'medium',
        'status' => 'scheduled',
    ]);

    $this->withHeaders(authHeaders($assignee))
        ->getJson('/api/v1/schedules')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $entry->id);

    $this->withHeaders(authHeaders($assignee))
        ->patchJson("/api/v1/schedules/{$entry->id}", ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->withHeaders(authHeaders($outsider))
        ->getJson("/api/v1/schedules/{$entry->id}")
        ->assertNotFound();

    $this->withHeaders(authHeaders($outsider))
        ->getJson('/api/v1/schedules')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('summarizes upcoming completed and overdue schedules', function (): void {
    CarbonImmutable::setTestNow('2026-07-15 12:00:00');
    $user = User::factory()->create();

    foreach ([
        ['2026-07-14', '09:00', 'Past due', 'scheduled'],
        ['2026-07-16', '09:00', 'Next task', 'in_progress'],
        ['2026-07-13', '09:00', 'Finished', 'completed'],
        ['2026-07-12', '09:00', 'Cancelled', 'cancelled'],
    ] as [$date, $time, $task, $status]) {
        ScheduleEntry::query()->create([
            'owner_id' => $user->id,
            'scheduled_date' => $date,
            'start_time' => $time,
            'task' => $task,
            'priority' => 'medium',
            'status' => $status,
        ]);
    }

    $this->withHeaders(authHeaders($user))
        ->getJson('/api/v1/schedules/summary')
        ->assertOk()
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.upcoming', 1)
        ->assertJsonPath('data.completed', 1)
        ->assertJsonPath('data.overdue', 1);
});

it('imports and exports excel-compatible csv schedules', function (): void {
    $user = User::factory()->create();
    $assignee = User::factory()->create(['email' => 'teammate@example.com']);
    $headers = authHeaders($user);
    $csv = implode("\n", [
        'Date,Start Time,End Time,Task,Description,Priority,Status,Assignee Email',
        '2026-07-22,08:30,09:30,Imported task,From spreadsheet,urgent,scheduled,teammate@example.com',
    ]);

    $this->withHeaders($headers)
        ->post('/api/v1/schedules/import', [
            'file' => UploadedFile::fake()->createWithContent('schedules.csv', $csv),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.imported', 1)
        ->assertJsonCount(0, 'data.errors');

    $this->assertDatabaseHas('schedule_entries', [
        'owner_id' => $user->id,
        'assignee_id' => $assignee->id,
        'task' => 'Imported task',
        'priority' => 'urgent',
    ]);

    $response = $this->withHeaders($headers)->get('/api/v1/schedules/export');
    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())
        ->toContain('Imported task')
        ->toContain('teammate@example.com');
});

it('validates schedule times and workflow values', function (): void {
    $this->withHeaders(authHeaders())
        ->postJson('/api/v1/schedules', [
            'scheduled_date' => '2026-07-20',
            'start_time' => '17:00',
            'end_time' => '09:00',
            'task' => '',
            'priority' => 'critical',
            'status' => 'unknown',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['end_time', 'task', 'priority', 'status']);
});
