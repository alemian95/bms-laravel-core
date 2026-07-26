<?php

namespace App\Data\Settings;

use App\Models\User;

final readonly class UpdateProfileData
{
    public function __construct(
        public User $user,
        public string $name,
        public string $email,
    ) {}
}
