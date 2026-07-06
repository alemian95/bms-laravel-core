<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Data\Auth\LoginData;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

/**
 * @implements Action<LoginData, NewAccessToken>
 */
final class AuthenticateApiUser implements Action
{
    /**
     * @param  LoginData  $input
     *
     * @throws InvalidApiCredentialsException
     */
    public function handle(mixed $input): NewAccessToken
    {
        $user = User::where('email', $input->email)->first();

        if (! $user || ! Hash::check($input->password, $user->password)) {
            throw new InvalidApiCredentialsException;
        }

        return $user->createToken($input->deviceName, ['*']);
    }
}
