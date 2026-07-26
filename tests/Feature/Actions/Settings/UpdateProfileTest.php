<?php

use App\Actions\Settings\UpdateProfile;
use App\Data\Settings\UpdateProfileData;
use App\Models\User;

it('updates name and email', function () {
    $user = User::factory()->create(['name' => 'Old', 'email' => 'old@example.com']);

    app(UpdateProfile::class)->handle(new UpdateProfileData(
        user: $user,
        name: 'New Name',
        email: 'new@example.com',
    ));

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

it('resets email verification when the email changes', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    app(UpdateProfile::class)->handle(new UpdateProfileData(
        user: $user,
        name: $user->name,
        email: 'changed@example.com',
    ));

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('keeps email verification when the email is unchanged', function () {
    $user = User::factory()->create([
        'email' => 'stable@example.com',
        'email_verified_at' => now(),
    ]);

    app(UpdateProfile::class)->handle(new UpdateProfileData(
        user: $user,
        name: 'Renamed Only',
        email: 'stable@example.com',
    ));

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});
