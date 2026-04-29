<?php

namespace App\Services\Auth;

use App\Models\User;

class ApiTokenRevoker
{
    public function revoke(User $user, int $tokenId): void
    {
        $user->tokens()->whereKey($tokenId)->delete();
    }
}
