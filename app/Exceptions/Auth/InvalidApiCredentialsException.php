<?php

namespace App\Exceptions\Auth;

use DomainException;

class InvalidApiCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.');
    }
}
