<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Data\Auth\RevokeTokenData;

/**
 * @implements Action<RevokeTokenData, void>
 */
final class RevokeApiToken implements Action
{
    /**
     * @param  RevokeTokenData  $input
     */
    public function handle(mixed $input): void
    {
        $input->user->tokens()->whereKey($input->tokenId)->delete();
    }
}
