<?php

namespace App\Actions\Auth;

use App\Data\Auth\LoginData;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

final class AuthenticateApiUser
{
    /**
     * @throws InvalidApiCredentialsException
     */
    public function handle(LoginData $input): NewAccessToken
    {
        $user = User::where('email', $input->email)->first();

        if (! $user || ! Hash::check($input->password, $user->password)) {
            throw new InvalidApiCredentialsException;
        }

        return $user->createToken($input->deviceName, ['*']);
    }
}
