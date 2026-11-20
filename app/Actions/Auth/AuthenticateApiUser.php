<?php

namespace App\Actions\Auth;

use App\Data\Auth\LoginData;
use App\Enums\TokenPreset;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Exceptions\Auth\InvalidTwoFactorCodeException;
use App\Exceptions\Auth\TwoFactorCodeRequiredException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\NewAccessToken;

final class AuthenticateApiUser
{
    public function __construct(private TwoFactorAuthenticationProvider $twoFactor) {}

    /**
     * @throws InvalidApiCredentialsException
     * @throws TwoFactorCodeRequiredException
     * @throws InvalidTwoFactorCodeException
     */
    public function handle(LoginData $input): NewAccessToken
    {
        $user = User::where('email', $input->email)->first();

        if (! $user || ! Hash::check($input->password, $user->password)) {
            throw new InvalidApiCredentialsException;
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $this->verifySecondFactor($user, $input);
        }

        return $user->createToken($input->deviceName, TokenPreset::MobileApp->abilities());
    }

    private function verifySecondFactor(User $user, LoginData $input): void
    {
        if ($input->code !== null) {
            $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

            if (! $this->twoFactor->verify($secret, $input->code)) {
                throw new InvalidTwoFactorCodeException;
            }

            return;
        }

        if ($input->recoveryCode === null) {
            throw new TwoFactorCodeRequiredException;
        }

        $recoveryCode = collect($user->recoveryCodes())
            ->first(fn (string $code): bool => hash_equals($code, $input->recoveryCode));

        if ($recoveryCode === null) {
            throw new InvalidTwoFactorCodeException;
        }

        $user->replaceRecoveryCode($recoveryCode);
    }
}
