<?php

namespace App\Services\Bookmarks;

class BookmarkUrlNormalizer
{
    public function normalize(string $url): string
    {
        $url = trim($url);
        $hashPos = strpos($url, '#');

        return $hashPos === false ? $url : substr($url, 0, $hashPos);
    }
}
