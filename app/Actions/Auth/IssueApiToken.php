<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Data\Auth\IssueTokenData;
use Laravel\Sanctum\NewAccessToken;

/**
 * @implements Action<IssueTokenData, NewAccessToken>
 */
final class IssueApiToken implements Action
{
    /**
     * @param  IssueTokenData  $input
     */
    public function handle(mixed $input): NewAccessToken
    {
        return $input->user->createToken($input->name, $input->preset->abilities());
    }
}
