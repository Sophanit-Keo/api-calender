<?php

use App\Models\User;
use App\Models\WorkShiftTemplate;
use Database\Seeders\GlobalShiftTemplateSeeder;
use Illuminate\Http\UploadedFile;

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

it('returns every user as a roster row with shared global shift codes', function (): void {
    $this->seed(GlobalShiftTemplateSeeder::class);
    $userA = User::factory()->create(['name' => 'Alpha', 'staff_id' => 'AKM001', 'position' => 'QA Manager']);
    $userB = User::factory()->create(['name' => 'Beta', 'staff_id' => 'AKM002']);
    $dayShift = WorkShiftTemplate::query()->whereNull('user_id')->where('code', '8')->firstOrFail();

    $this->withHeaders(authHeaders())
        ->putJson('/api/v1/work-schedule/roster/cell', [
            'user_id' => $userA->id,
            'work_date' => '2026-07-10',
            'work_shift_template_id' => $dayShift->id,
        ])->assertOk()
        ->assertJsonPath('data.code', '8');

    $response = $this->withHeaders(authHeaders())
        ->getJson('/api/v1/work-schedule/roster?from=2026-07-10&to=2026-07-16')
        ->assertOk();

    $staff = collect($response->json('data.staff'));
    expect($staff->firstWhere('id', $userA->id)['entries']['2026-07-10']['code'])->toBe('8');
    expect($staff->firstWhere('id', $userB->id)['entries'])->not->toHaveKey('2026-07-10');
    expect(collect($response->json('data.codes'))->pluck('code'))->toContain('AL', 'ML', '8D');
});

it('surfaces a per-user cycle shift in the roster grid even though it is absent from global codes', function (): void {
    $this->seed(GlobalShiftTemplateSeeder::class);
    $staff = User::factory()->create(['staff_id' => 'AKM099']);

    $assignments = array_fill(0, 31, null);
    $assignments[0] = 'day';

    $this->withHeaders(authHeaders($staff))
        ->putJson('/api/v1/work-schedule/cycles/2026-06-26', ['assignments' => $assignments])
        ->assertOk();

    $personalDay = WorkShiftTemplate::query()->where('user_id', $staff->id)->where('code', 'day')->firstOrFail();

    $response = $this->withHeaders(authHeaders())
        ->getJson('/api/v1/work-schedule/roster?from=2026-06-26&to=2026-07-02')
        ->assertOk();

    $entry = collect($response->json('data.staff'))->firstWhere('id', $staff->id)['entries']['2026-06-26'];
    expect($entry['id'])->toBe($personalDay->id)->and($entry['code'])->toBe('day');
    expect(collect($response->json('data.codes'))->pluck('id'))->not->toContain($personalDay->id);
});

it('lets any authenticated user edit any other user roster cell and clear a range', function (): void {
    $this->seed(GlobalShiftTemplateSeeder::class);
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $leave = WorkShiftTemplate::query()->whereNull('user_id')->where('code', 'AL')->firstOrFail();

    $this->withHeaders(authHeaders($otherUser))
        ->putJson('/api/v1/work-schedule/roster/cell', [
            'user_id' => $owner->id,
            'work_date' => '2026-07-12',
            'work_shift_template_id' => $leave->id,
        ])->assertOk()
        ->assertJsonPath('data.code', 'AL');

    $this->assertDatabaseHas('work_schedule_days', [
        'user_id' => $owner->id,
        'work_date' => '2026-07-12',
        'work_shift_template_id' => $leave->id,
    ]);

    $this->withHeaders(authHeaders($otherUser))
        ->deleteJson("/api/v1/work-schedule/roster/staff/{$owner->id}?from=2026-07-12&to=2026-07-12")
        ->assertNoContent();

    $this->assertDatabaseMissing('work_schedule_days', [
        'user_id' => $owner->id,
        'work_date' => '2026-07-12',
    ]);
});

it('shows roster grid assignments on the personal calendar shift overlay', function (): void {
    $this->seed(GlobalShiftTemplateSeeder::class);
    $owner = User::factory()->create();
    $shift = WorkShiftTemplate::query()->whereNull('user_id')->where('code', '8N')->firstOrFail();

    $this->withHeaders(authHeaders())
        ->putJson('/api/v1/work-schedule/roster/cell', [
            'user_id' => $owner->id,
            'work_date' => '2026-07-14',
            'work_shift_template_id' => $shift->id,
        ])->assertOk();

    $this->withHeaders(authHeaders($owner))
        ->getJson('/api/v1/work-schedule/days?from=2026-07-14&to=2026-07-14')
        ->assertOk()
        ->assertJsonPath('data.0.shift_template.code', '8N');
});

it('defaults the roster to the current 26th-anchored cycle split into 4 weeks', function (): void {
    $this->withHeaders(authHeaders())
        ->getJson('/api/v1/work-schedule/roster?date=2026-07-10')
        ->assertOk()
        ->assertJsonPath('data.from', '2026-06-26')
        ->assertJsonPath('data.to', '2026-07-25')
        ->assertJsonCount(4, 'data.weeks')
        ->assertJsonPath('data.weeks.0.from', '2026-06-26')
        ->assertJsonPath('data.weeks.0.to', '2026-07-02')
        ->assertJsonPath('data.weeks.3.to', '2026-07-25');
});

it('lets an admin edit staff id, position, and group inline from the roster', function (): void {
    $staff = User::factory()->create(['staff_id' => 'OLD001', 'position' => 'Nurse']);

    $this->withHeaders(authHeaders())
        ->putJson("/api/v1/work-schedule/roster/staff/{$staff->id}", [
            'staff_id' => 'NEW001',
            'position' => 'Senior Nurse',
            'group' => 'ICU',
        ])->assertOk()
        ->assertJsonPath('data.staff_id', 'NEW001')
        ->assertJsonPath('data.position', 'Senior Nurse')
        ->assertJsonPath('data.group', 'ICU');

    $this->assertDatabaseHas('users', [
        'id' => $staff->id,
        'staff_id' => 'NEW001',
        'position' => 'Senior Nurse',
        'group' => 'ICU',
    ]);

    $response = $this->withHeaders(authHeaders())
        ->getJson('/api/v1/work-schedule/roster?date=2026-07-10')
        ->assertOk();

    $row = collect($response->json('data.staff'))->firstWhere('id', $staff->id);
    expect($row['group'])->toBe('ICU');
});

it('groups everyone working on a given day for the "who is working today" endpoint', function (): void {
    $this->seed(GlobalShiftTemplateSeeder::class);
    $userA = User::factory()->create(['name' => 'Alpha']);
    $userB = User::factory()->create(['name' => 'Beta']);
    $userC = User::factory()->create(['name' => 'Gamma']);
    $dayShift = WorkShiftTemplate::query()->whereNull('user_id')->where('code', '8')->firstOrFail();
    $nightShift = WorkShiftTemplate::query()->whereNull('user_id')->where('code', '8N')->firstOrFail();

    $headers = authHeaders();
    foreach ([[$userA, $dayShift], [$userB, $dayShift], [$userC, $nightShift]] as [$user, $shift]) {
        $this->withHeaders($headers)->putJson('/api/v1/work-schedule/roster/cell', [
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'work_shift_template_id' => $shift->id,
        ])->assertOk();
    }

    $response = $this->withHeaders($headers)
        ->getJson('/api/v1/work-schedule/today?date=2026-07-10')
        ->assertOk()
        ->assertJsonPath('data.date', '2026-07-10')
        ->assertJsonCount(2, 'data.shifts');

    $dayGroup = collect($response->json('data.shifts'))->firstWhere('shift_template.code', '8');
    expect(collect($dayGroup['staff'])->pluck('name'))->toContain('Alpha', 'Beta');

    $nightGroup = collect($response->json('data.shifts'))->firstWhere('shift_template.code', '8N');
    expect(collect($nightGroup['staff'])->pluck('name'))->toContain('Gamma');
});

it('exports the roster as csv and imports it back, overwriting existing cells', function (): void {
    $this->seed(GlobalShiftTemplateSeeder::class);
    $staff = User::factory()->create(['staff_id' => 'AKM010', 'name' => 'Import Target', 'group' => 'Ward A']);
    $dayShift = WorkShiftTemplate::query()->whereNull('user_id')->where('code', '8')->firstOrFail();
    $leave = WorkShiftTemplate::query()->whereNull('user_id')->where('code', 'AL')->firstOrFail();
    $headers = authHeaders(User::factory()->create(['staff_id' => 'ADMIN001']));

    $this->withHeaders($headers)->putJson('/api/v1/work-schedule/roster/cell', [
        'user_id' => $staff->id,
        'work_date' => '2026-07-10',
        'work_shift_template_id' => $dayShift->id,
    ])->assertOk();

    $exportResponse = $this->withHeaders($headers)
        ->get('/api/v1/work-schedule/roster/export?period=week&date=2026-07-10');
    $exportResponse->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $exportResponse->streamedContent();
    expect($csv)->toContain('ID,Group,Name,Position')
        ->toContain('AKM010')
        ->toContain('Ward A')
        ->toContain('Import Target')
        ->toContain('2026-07-10');

    // Flip the 2026-07-10 assignment to a leave code, keeping the export's exact column layout, and re-import it.
    $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($csv, "\xEF\xBB\xBF\r\n"))));
    $header = str_getcsv(array_shift($lines));
    $dateIndex = array_search('2026-07-10', $header, true);
    $rows = array_map(fn (string $line): array => str_getcsv($line), $lines);
    $rows = array_map(function (array $row) use ($dateIndex): array {
        $row[$dateIndex] = 'AL';

        return $row;
    }, $rows);

    $handle = fopen('php://temp', 'r+b');
    fputcsv($handle, $header);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    $importResponse = $this->withHeaders($headers)
        ->post('/api/v1/work-schedule/roster/import', [
            'file' => UploadedFile::fake()->createWithContent('roster.csv', $csv),
        ], ['Accept' => 'application/json']);
    $importResponse->assertCreated()->assertJsonCount(0, 'data.errors');

    $this->assertDatabaseHas('work_schedule_days', [
        'user_id' => $staff->id,
        'work_date' => '2026-07-10',
        'work_shift_template_id' => $leave->id,
    ]);
});
