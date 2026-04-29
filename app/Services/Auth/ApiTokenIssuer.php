<?php

namespace App\Services\Auth;

use App\Data\Auth\IssueTokenData;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class ApiTokenIssuer
{
    public function issue(User $user, IssueTokenData $data): NewAccessToken
    {
        return $user->createToken($data->name, $data->preset->abilities());
    }
}
