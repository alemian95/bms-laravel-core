<?php

namespace App\Data\Auth;

use App\Enums\TokenPreset;

final readonly class IssueTokenData
{
    public function __construct(
        public string $name,
        public TokenPreset $preset,
    ) {}
}
