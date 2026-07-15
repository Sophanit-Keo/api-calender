<?php

use App\Models\RosterCode;
use App\Models\User;
use Database\Seeders\RosterCodeSeeder;

beforeEach(function (): void {
    $this->seed(RosterCodeSeeder::class);
});

it('returns every user as a roster row with codes for the requested range', function (): void {
    $userA = User::factory()->create(['name' => 'Alpha', 'staff_id' => 'AKM001', 'position' => 'QA Manager']);
    $userB = User::factory()->create(['name' => 'Beta', 'staff_id' => 'AKM002', 'position' => 'QA Supervisor']);
    $dayShift = RosterCode::query()->where('code', '8')->firstOrFail();

    $this->withHeaders(authHeaders())
        ->putJson('/api/v1/roster/cell', [
            'user_id' => $userA->id,
            'work_date' => '2026-07-10',
            'roster_code_id' => $dayShift->id,
        ])->assertOk()
        ->assertJsonPath('data.code', '8');

    $response = $this->withHeaders(authHeaders())
        ->getJson('/api/v1/roster?from=2026-07-10&to=2026-07-16')
        ->assertOk();

    $staff = collect($response->json('data.staff'));

    expect($staff->firstWhere('id', $userA->id)['entries']['2026-07-10']['code'])->toBe('8');
    expect($staff->firstWhere('id', $userB->id)['entries'])->not->toHaveKey('2026-07-10');
    expect($response->json('data.codes'))->not->toBeEmpty();
});

it('lets any authenticated user edit any other user roster cell', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $leave = RosterCode::query()->where('code', 'AL')->firstOrFail();

    $this->withHeaders(authHeaders($otherUser))
        ->putJson('/api/v1/roster/cell', [
            'user_id' => $owner->id,
            'work_date' => '2026-07-12',
            'roster_code_id' => $leave->id,
        ])->assertOk()
        ->assertJsonPath('data.code', 'AL');

    $this->assertDatabaseHas('roster_entries', [
        'user_id' => $owner->id,
        'work_date' => '2026-07-12',
        'roster_code_id' => $leave->id,
    ]);

    // Clearing a cell (roster_code_id: null) removes the entry.
    $this->withHeaders(authHeaders($otherUser))
        ->putJson('/api/v1/roster/cell', [
            'user_id' => $owner->id,
            'work_date' => '2026-07-12',
            'roster_code_id' => null,
        ])->assertOk()
        ->assertJsonPath('data', null);

    $this->assertDatabaseMissing('roster_entries', [
        'user_id' => $owner->id,
        'work_date' => '2026-07-12',
    ]);
});

it('clears a range of roster entries for a staff member', function (): void {
    $user = User::factory()->create();
    $shift = RosterCode::query()->where('code', '8')->firstOrFail();

    foreach (['2026-07-10', '2026-07-11', '2026-07-12'] as $date) {
        $this->withHeaders(authHeaders())
            ->putJson('/api/v1/roster/cell', [
                'user_id' => $user->id,
                'work_date' => $date,
                'roster_code_id' => $shift->id,
            ])->assertOk();
    }

    $this->withHeaders(authHeaders())
        ->deleteJson("/api/v1/roster/staff/{$user->id}?from=2026-07-10&to=2026-07-12")
        ->assertNoContent();

    expect($user->rosterEntries()->count())->toBe(0);
});

it('rejects roster date ranges over 62 days and unknown codes', function (): void {
    $this->withHeaders(authHeaders())
        ->getJson('/api/v1/roster?from=2026-01-01&to=2026-12-31')
        ->assertUnprocessable();

    $this->withHeaders(authHeaders())
        ->putJson('/api/v1/roster/cell', [
            'user_id' => User::factory()->create()->id,
            'work_date' => '2026-07-10',
            'roster_code_id' => 999999,
        ])->assertUnprocessable();
});

it('allows registering a user with staff_id and position for the roster', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'New Staff',
        'email' => 'new.staff@example.com',
        'password' => 'password123',
        'staff_id' => 'AKM999',
        'position' => 'QC1-Physicochemical',
    ])->assertCreated()
        ->assertJsonPath('data.user.staff_id', 'AKM999')
        ->assertJsonPath('data.user.position', 'QC1-Physicochemical');
});
