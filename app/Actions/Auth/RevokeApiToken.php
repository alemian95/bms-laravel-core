<?php

namespace App\Actions\Auth;

use App\Data\Auth\RevokeTokenData;

final class RevokeApiToken
{
    public function handle(RevokeTokenData $input): void
    {
        $input->user->tokens()->whereKey($input->tokenId)->delete();
    }
}
