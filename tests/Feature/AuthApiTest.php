<?php

use App\Models\User;

it('requires bearer authentication for protected api endpoints', function (): void {
    $this->getJson('/api/v1/notes')->assertUnauthorized();
    $this->getJson('/api/v1/calendar/convert?date=2026-06-27')->assertUnauthorized();
});

it('registers a user and returns an api token', function (): void {
    $token = $this->postJson('/api/v1/auth/register', [
        'name' => 'Phanit',
        'email' => 'phanit@example.com',
        'password' => 'password123',
        'device_name' => 'tests',
    ])->assertCreated()
        ->assertJsonPath('data.user.email', 'phanit@example.com')
        ->json('data.token');

    expect($token)->toBeString()->not->toBeEmpty();

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'phanit@example.com');
});

it('registers a user with staff id, position, and group', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Dara',
        'email' => 'dara@example.com',
        'password' => 'password123',
        'staff_id' => 'AKM099',
        'position' => 'Technician',
        'group' => 'Maintenance',
    ])->assertCreated()
        ->assertJsonPath('data.user.staff_id', 'AKM099')
        ->assertJsonPath('data.user.position', 'Technician')
        ->assertJsonPath('data.user.group', 'Maintenance');

    $this->assertDatabaseHas('users', [
        'email' => 'dara@example.com',
        'staff_id' => 'AKM099',
        'position' => 'Technician',
        'group' => 'Maintenance',
    ]);
});

it('logs in and revokes the current token on logout', function (): void {
    User::factory()->create([
        'email' => 'student@example.com',
        'password' => 'secret-password',
    ]);

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'student@example.com',
        'password' => 'secret-password',
    ])->assertOk()->json('data.token');

    $headers = ['Authorization' => 'Bearer '.$token];

    $this->withHeaders($headers)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertDatabaseCount('api_tokens', 0);

    $this->withHeaders($headers)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
