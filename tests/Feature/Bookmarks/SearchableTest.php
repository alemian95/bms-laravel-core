<?php

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->in(__DIR__);

test('toSearchableArray exposes the expected fields including category name', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Tech']);
    $bookmark = Bookmark::factory()->for($user)->for($category)->create([
        'title' => 'Hello',
        'author' => 'Alice',
        'domain' => 'example.com',
        'content_text' => 'Body text',
        'status' => 'parsed',
    ]);

    $payload = $bookmark->toSearchableArray();

    expect($payload)
        ->toMatchArray([
            'id' => $bookmark->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Hello',
            'author' => 'Alice',
            'domain' => 'example.com',
            'category_name' => 'Tech',
            'content_text' => 'Body text',
            'status' => 'parsed',
        ])
        ->and($payload['created_at'])->toBeInt();
});

test('toSearchableArray returns null category_name when bookmark has no category', function () {
    $bookmark = Bookmark::factory()->create([
        'category_id' => null,
        'status' => 'parsed',
        'content_text' => 'x',
    ]);

    expect($bookmark->toSearchableArray()['category_name'])->toBeNull();
});

test('shouldBeSearchable requires status parsed and non-empty content_text', function () {
    $parsedWithContent = Bookmark::factory()->make(['status' => 'parsed', 'content_text' => 'hi']);
    $parsedWithoutContent = Bookmark::factory()->make(['status' => 'parsed', 'content_text' => null]);
    $pending = Bookmark::factory()->make(['status' => 'pending', 'content_text' => 'hi']);
    $failed = Bookmark::factory()->make(['status' => 'failed', 'content_text' => 'hi']);

    expect($parsedWithContent->shouldBeSearchable())->toBeTrue()
        ->and($parsedWithoutContent->shouldBeSearchable())->toBeFalse()
        ->and($pending->shouldBeSearchable())->toBeFalse()
        ->and($failed->shouldBeSearchable())->toBeFalse();
});
