<?php

use App\Actions\Auth\AuthenticateApiUser;
use App\Data\Auth\LoginData;
use App\Enums\TokenPreset;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Exceptions\Auth\InvalidTwoFactorCodeException;
use App\Exceptions\Auth\TwoFactorCodeRequiredException;
use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\NewAccessToken;
use PragmaRX\Google2FA\Google2FA;

function twoFactorLogin(?string $code = null, ?string $recoveryCode = null): LoginData
{
    return new LoginData(
        email: 'jane@example.com',
        password: 'secret-password-1',
        deviceName: 'iPhone',
        code: $code,
        recoveryCode: $recoveryCode,
    );
}

it('issues a token when credentials are valid', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'secret-password-1',
    ]);

    $token = app(AuthenticateApiUser::class)->handle(new LoginData(
        email: 'jane@example.com',
        password: 'secret-password-1',
        deviceName: 'iPhone',
    ));

    expect($token)->toBeInstanceOf(NewAccessToken::class)
        ->and($token->accessToken->tokenable_id)->toBe($user->id)
        ->and($token->accessToken->name)->toBe('iPhone')
        ->and($token->accessToken->abilities)->toBe(TokenPreset::MobileApp->abilities());
});

it('throws InvalidApiCredentialsException for wrong password', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'correct-password',
    ]);

    expect(fn () => app(AuthenticateApiUser::class)->handle(new LoginData(
        email: 'jane@example.com',
        password: 'wrong-password',
        deviceName: 'iPhone',
    )))->toThrow(InvalidApiCredentialsException::class);
});

it('throws InvalidApiCredentialsException for unknown email', function () {
    expect(fn () => app(AuthenticateApiUser::class)->handle(new LoginData(
        email: 'noone@example.com',
        password: 'whatever',
        deviceName: 'iPhone',
    )))->toThrow(InvalidApiCredentialsException::class);
});

describe('with two factor authentication enabled', function () {
    beforeEach(function () {
        $this->secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();
        $this->user = User::factory()->withTwoFactor()->create([
            'email' => 'jane@example.com',
            'password' => 'secret-password-1',
            'two_factor_secret' => encrypt($this->secret),
        ]);
    });

    it('requires a code', function () {
        expect(fn () => app(AuthenticateApiUser::class)->handle(twoFactorLogin()))
            ->toThrow(TwoFactorCodeRequiredException::class);

        expect($this->user->tokens()->count())->toBe(0);
    });

    it('rejects an invalid code', function () {
        expect(fn () => app(AuthenticateApiUser::class)->handle(twoFactorLogin(code: '000000')))
            ->toThrow(InvalidTwoFactorCodeException::class);

        expect($this->user->tokens()->count())->toBe(0);
    });

    it('issues a token with a valid code', function () {
        $code = app(Google2FA::class)->getCurrentOtp($this->secret);

        $token = app(AuthenticateApiUser::class)->handle(twoFactorLogin(code: $code));

        expect($token->accessToken->tokenable_id)->toBe($this->user->id);
    });

    it('issues a token with a valid recovery code and consumes it', function () {
        $token = app(AuthenticateApiUser::class)->handle(twoFactorLogin(recoveryCode: 'recovery-code-1'));

        expect($token->accessToken->tokenable_id)->toBe($this->user->id)
            ->and($this->user->fresh()->recoveryCodes())->not->toContain('recovery-code-1');
    });

    it('rejects an invalid recovery code', function () {
        expect(fn () => app(AuthenticateApiUser::class)->handle(twoFactorLogin(recoveryCode: 'wrong-code')))
            ->toThrow(InvalidTwoFactorCodeException::class);

        expect($this->user->tokens()->count())->toBe(0);
    });
});
