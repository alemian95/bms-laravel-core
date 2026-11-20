<?php

namespace App\Exceptions\Auth;

use DomainException;

class TwoFactorCodeRequiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('A two factor authentication code is required.');
    }
}
