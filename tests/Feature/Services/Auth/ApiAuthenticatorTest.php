<?php

use App\Data\Auth\LoginData;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Models\User;
use App\Services\Auth\ApiAuthenticator;
use Laravel\Sanctum\NewAccessToken;

it('issues a token when credentials are valid', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'secret-password-1',
    ]);

    $token = app(ApiAuthenticator::class)->login(new LoginData(
        email: 'jane@example.com',
        password: 'secret-password-1',
        deviceName: 'iPhone',
    ));

    expect($token)->toBeInstanceOf(NewAccessToken::class)
        ->and($token->accessToken->tokenable_id)->toBe($user->id)
        ->and($token->accessToken->name)->toBe('iPhone')
        ->and($token->accessToken->abilities)->toBe(['*']);
});

it('throws InvalidApiCredentialsException for wrong password', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'correct-password',
    ]);

    expect(fn () => app(ApiAuthenticator::class)->login(new LoginData(
        email: 'jane@example.com',
        password: 'wrong-password',
        deviceName: 'iPhone',
    )))->toThrow(InvalidApiCredentialsException::class);
});

it('throws InvalidApiCredentialsException for unknown email', function () {
    expect(fn () => app(ApiAuthenticator::class)->login(new LoginData(
        email: 'noone@example.com',
        password: 'whatever',
        deviceName: 'iPhone',
    )))->toThrow(InvalidApiCredentialsException::class);
});
