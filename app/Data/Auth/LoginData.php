<?php

namespace App\Data\Auth;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
