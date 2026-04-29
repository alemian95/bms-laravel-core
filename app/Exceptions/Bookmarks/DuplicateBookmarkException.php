<?php

namespace App\Exceptions\Bookmarks;

use DomainException;

class DuplicateBookmarkException extends DomainException
{
    public function __construct(public readonly string $url)
    {
        parent::__construct("Bookmark with URL [{$url}] already exists for this user.");
    }
}
