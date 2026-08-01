<?php

namespace App\Actions\Auth;

use App\Data\Auth\IssueTokenData;
use Laravel\Sanctum\NewAccessToken;

final class IssueApiToken
{
    public function handle(IssueTokenData $input): NewAccessToken
    {
        return $input->user->createToken($input->name, $input->preset->abilities());
    }
}
