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
