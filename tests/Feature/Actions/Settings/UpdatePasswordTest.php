<?php

use App\Actions\Settings\UpdatePassword;
use App\Data\Settings\UpdatePasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('updates the user password with a hashed value', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    app(UpdatePassword::class)->handle(new UpdatePasswordData($user, 'brand-new-password'));

    $stored = $user->fresh()->password;
    expect(Hash::check('brand-new-password', $stored))->toBeTrue()
        ->and($stored)->not->toBe('brand-new-password');
});
