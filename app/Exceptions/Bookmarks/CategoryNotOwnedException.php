<?php

namespace App\Exceptions\Bookmarks;

use DomainException;

class CategoryNotOwnedException extends DomainException
{
    public function __construct(public readonly int $categoryId)
    {
        parent::__construct("Category [{$categoryId}] does not belong to the current user.");
    }
}
