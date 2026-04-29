<?php

use App\Models\User;

it('returns a token on valid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'good-password-1',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'good-password-1',
        'device_name' => 'iPhone',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token']);

    expect($response->json('token'))->toBeString()->not->toBe('');
});

it('returns 401 on invalid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'good-password-1',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'WRONG',
        'device_name' => 'iPhone',
    ]);

    $response->assertUnauthorized()
        ->assertJson(['message' => 'The provided credentials are incorrect.']);
});

it('returns 422 when fields are missing', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});
