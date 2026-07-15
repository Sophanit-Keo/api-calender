<?php

use App\Models\User;

it('returns and updates work schedule settings with shift templates', function (): void {
    $this->withHeaders(authHeaders());

    $this->getJson('/api/v1/work-schedule/settings')
        ->assertOk()
        ->assertJsonPath('data.settings.system_type', 2)
        ->assertJsonPath('data.shift_templates.0.code', 'day');

    $response = $this->putJson('/api/v1/work-schedule/settings', [
        'system_type' => 3,
        'remind' => false,
        'reminder_minutes_before' => 45,
        'shift_templates' => [
            ['code' => 's1', 'name' => 'Shift 1', 'start_time' => '07:30', 'end_time' => '15:30', 'sort_order' => 1],
            ['code' => 's2', 'name' => 'Shift 2', 'start_time' => '15:30', 'end_time' => '23:30', 'sort_order' => 2],
            ['code' => 's3', 'name' => 'Shift 3', 'start_time' => '23:30', 'end_time' => '07:30', 'sort_order' => 3],
        ],
    ])->assertOk()
        ->assertJsonPath('data.settings.system_type', 3)
        ->assertJsonPath('data.settings.remind', false);

    expect(collect($response->json('data.shift_templates'))->pluck('code'))->toContain('s3');
});

it('saves 26th anchored cycles and materializes blocked overnight work days', function (): void {
    $this->withHeaders(authHeaders());

    $assignments = array_fill(0, 31, null);
    $assignments[0] = 'night';
    $assignments[1] = 'day';

    $this->putJson('/api/v1/work-schedule/cycles/2026-06-26', [
        'assignments' => $assignments,
    ])->assertOk()
        ->assertJsonPath('data.cycle_start_date', '2026-06-26')
        ->assertJsonPath('data.assignments.0', 'night')
        ->assertJsonPath('data.assignments.1', 'day');

    $this->getJson('/api/v1/work-schedule/cycles/2026-06-26')
        ->assertOk()
        ->assertJsonCount(31, 'data.assignments');

    $this->getJson('/api/v1/work-schedule/days?from=2026-06-26&to=2026-06-27')
        ->assertOk()
        ->assertJsonPath('data.0.shift_template.code', 'night')
        ->assertJsonPath('data.0.blocked', false)
        ->assertJsonPath('data.1.shift_template.code', 'day')
        ->assertJsonPath('data.1.blocked', true);
});

it('validates work schedule inputs', function (): void {
    $this->withHeaders(authHeaders());

    $this->putJson('/api/v1/work-schedule/cycles/2026-06-25', [
        'assignments' => ['day'],
    ])->assertUnprocessable();

    $this->putJson('/api/v1/work-schedule/settings', [
        'system_type' => 4,
    ])->assertUnprocessable();

    $this->getJson('/api/v1/work-schedule/days?from=2026-06-28&to=2026-06-27')
        ->assertUnprocessable();
});

it('keeps each user default work schedule separate until another user opts in via user_id', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->withHeaders(authHeaders($userA))
        ->putJson('/api/v1/work-schedule/settings', [
            'shift_templates' => [
                ['code' => 'shared', 'name' => 'User A Shift', 'start_time' => '07:00', 'end_time' => '15:00'],
            ],
        ])->assertOk();

    $this->withHeaders(authHeaders($userB))
        ->putJson('/api/v1/work-schedule/settings', [
            'shift_templates' => [
                ['code' => 'shared', 'name' => 'User B Shift', 'start_time' => '15:00', 'end_time' => '23:00'],
            ],
        ])->assertOk();

    $assignmentsA = array_fill(0, 31, null);
    $assignmentsA[0] = 'shared';

    $this->withHeaders(authHeaders($userA))
        ->putJson('/api/v1/work-schedule/cycles/2026-06-26', [
            'assignments' => $assignmentsA,
        ])->assertOk();

    $this->withHeaders(authHeaders($userB))
        ->getJson('/api/v1/work-schedule/settings')
        ->assertOk()
        ->assertJsonFragment(['name' => 'User B Shift'])
        ->assertJsonMissing(['name' => 'User A Shift']);
});

it('lets any user view and edit another user work schedule via user_id', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->withHeaders(authHeaders($userA))
        ->putJson('/api/v1/work-schedule/settings', [
            'shift_templates' => [
                ['code' => 'shared', 'name' => 'User A Shift', 'start_time' => '07:00', 'end_time' => '15:00'],
            ],
        ])->assertOk();

    // User B reads and edits User A's work schedule by targeting user_id.
    $this->withHeaders(authHeaders($userB))
        ->getJson("/api/v1/work-schedule/settings?user_id={$userA->id}")
        ->assertOk()
        ->assertJsonFragment(['name' => 'User A Shift']);

    $this->withHeaders(authHeaders($userB))
        ->putJson('/api/v1/work-schedule/settings', [
            'user_id' => $userA->id,
            'shift_templates' => [
                ['code' => 'shared', 'name' => 'Edited By User B', 'start_time' => '08:00', 'end_time' => '16:00'],
            ],
        ])->assertOk()
        ->assertJsonFragment(['name' => 'Edited By User B']);

    $assignments = array_fill(0, 31, null);
    $assignments[0] = 'shared';

    $this->withHeaders(authHeaders($userB))
        ->putJson('/api/v1/work-schedule/cycles/2026-06-26', [
            'user_id' => $userA->id,
            'assignments' => $assignments,
        ])->assertOk()
        ->assertJsonPath('data.assignments.0', 'shared');

    $this->withHeaders(authHeaders($userB))
        ->getJson("/api/v1/work-schedule/days?user_id={$userA->id}&from=2026-06-26&to=2026-06-26")
        ->assertOk()
        ->assertJsonPath('data.0.shift_template.name', 'Edited By User B');

    // The edit is really User A's data, visible to User A directly too (no user_id needed).
    $this->withHeaders(authHeaders($userA))
        ->getJson('/api/v1/work-schedule/settings')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Edited By User B']);
});
