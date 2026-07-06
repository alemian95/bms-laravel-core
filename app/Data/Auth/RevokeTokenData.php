<?php

namespace App\Data\Auth;

use App\Models\User;

final readonly class RevokeTokenData
{
    public function __construct(
        public User $user,
        public int $tokenId,
    ) {}
}
