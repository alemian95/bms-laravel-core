<?php

namespace App\Exceptions\Auth;

use DomainException;

class InvalidTwoFactorCodeException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided two factor authentication code was invalid.');
    }
}
