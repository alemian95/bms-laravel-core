<?php

namespace App\Services;

use Embed\Embed;

class BookmarkMetadataExtractor
{
    public function __construct(private Embed $embed) {}

    /**
     * @return array{title: string|null, domain: string|null, author: string|null, thumbnail_url: string|null}
     */
    public function extract(string $url): array
    {
        $info = $this->embed->get($url);

        $host = $info->url?->getHost()
            ?: (parse_url($url, PHP_URL_HOST) ?: null);

        return [
            'title' => $info->title ?: null,
            'domain' => $host ?: null,
            'author' => $info->authorName ?: null,
            'thumbnail_url' => $info->image ? (string) $info->image : null,
        ];
    }
}
