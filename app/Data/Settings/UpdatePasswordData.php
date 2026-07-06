<?php

namespace App\Data\Settings;

use App\Models\User;

final readonly class UpdatePasswordData
{
    public function __construct(
        public User $user,
        public string $password,
    ) {}
}
