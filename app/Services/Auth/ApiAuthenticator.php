<?php

namespace App\Services\Auth;

use App\Data\Auth\LoginData;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class ApiAuthenticator
{
    /**
     * @throws InvalidApiCredentialsException
     */
    public function login(LoginData $data): NewAccessToken
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidApiCredentialsException;
        }

        return $user->createToken($data->deviceName, ['*']);
    }
}
