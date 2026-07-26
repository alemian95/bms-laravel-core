<?php

namespace App\Data\Auth;

use App\Enums\TokenPreset;
use App\Models\User;

final readonly class IssueTokenData
{
    public function __construct(
        public User $user,
        public string $name,
        public TokenPreset $preset,
    ) {}
}
