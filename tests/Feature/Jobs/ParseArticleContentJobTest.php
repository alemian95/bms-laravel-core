<?php

use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\User;
use App\Services\ArticleContentParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('parses article content and stores clean html plus plain text', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->pending()->create([
        'url' => 'https://example.com/article',
    ]);

    $parser = Mockery::mock(ArticleContentParser::class);
    $parser->shouldReceive('parse')->once()
        ->andReturn([
            'content_html' => '<p>Hello world</p>',
            'content_text' => 'Hello world',
        ]);

    $job = new ParseArticleContentJob($bookmark);
    $job->handle($parser);

    expect($job->bookmark->content_html)->toBe('<p>Hello world</p>')
        ->and($job->bookmark->content_text)->toBe('Hello world');

    $this->assertDatabaseHas('bookmarks', [
        'id' => $bookmark->id,
        'content_html' => '<p>Hello world</p>',
    ]);
});

test('stores nulls when parser returns nulls and does not touch status', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'status' => 'parsed',
        'content_html' => null,
        'content_text' => null,
    ]);

    $parser = Mockery::mock(ArticleContentParser::class);
    $parser->shouldReceive('parse')->once()->andReturn([
        'content_html' => null,
        'content_text' => null,
    ]);

    (new ParseArticleContentJob($bookmark))->handle($parser);

    expect($bookmark->fresh()->status)->toBe('parsed')
        ->and($bookmark->fresh()->content_html)->toBeNull();
});
