<?php

use App\Services\Bookmarks\BookmarkUrlNormalizer;

it('strips fragments from urls', function () {
    $normalizer = new BookmarkUrlNormalizer;

    expect($normalizer->normalize('https://example.com/article#heading'))
        ->toBe('https://example.com/article');
});

it('trims surrounding whitespace', function () {
    $normalizer = new BookmarkUrlNormalizer;

    expect($normalizer->normalize('  https://example.com/article  '))
        ->toBe('https://example.com/article');
});

it('returns url unchanged when no fragment is present', function () {
    $normalizer = new BookmarkUrlNormalizer;

    expect($normalizer->normalize('https://example.com/article?ref=foo'))
        ->toBe('https://example.com/article?ref=foo');
});
