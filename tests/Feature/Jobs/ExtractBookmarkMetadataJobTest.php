<?php

use App\Jobs\ExtractBookmarkMetadataJob;
use App\Models\Bookmark;
use App\Models\User;
use App\Services\BookmarkMetadataExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('extracts metadata and marks the bookmark as parsed', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->pending()->create([
        'url' => 'https://example.com/article',
    ]);

    $extractor = Mockery::mock(BookmarkMetadataExtractor::class);
    $extractor->shouldReceive('extract')->once()
        ->with('https://example.com/article')
        ->andReturn([
            'title' => 'Example Article',
            'domain' => 'example.com',
            'author' => 'Jane Doe',
            'thumbnail_url' => 'https://cdn.example.com/thumb.jpg',
        ]);

    (new ExtractBookmarkMetadataJob($bookmark))->handle($extractor);

    $bookmark->refresh();
    expect($bookmark->status)->toBe('parsed')
        ->and($bookmark->title)->toBe('Example Article')
        ->and($bookmark->author)->toBe('Jane Doe')
        ->and($bookmark->thumbnail_url)->toBe('https://cdn.example.com/thumb.jpg')
        ->and($bookmark->domain)->toBe('example.com');
});

test('marks the bookmark as failed on final failure', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->pending()->create();

    $job = new ExtractBookmarkMetadataJob($bookmark);
    $job->failed(new RuntimeException('network down'));

    expect($bookmark->fresh()->status)->toBe('failed');
});

test('accepts null metadata values without failing', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->pending()->create();

    $extractor = Mockery::mock(BookmarkMetadataExtractor::class);
    $extractor->shouldReceive('extract')->once()->andReturn([
        'title' => null,
        'domain' => null,
        'author' => null,
        'thumbnail_url' => null,
    ]);

    (new ExtractBookmarkMetadataJob($bookmark))->handle($extractor);

    expect($bookmark->fresh()->status)->toBe('parsed');
});
