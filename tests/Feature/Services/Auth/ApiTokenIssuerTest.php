<?php

use App\Data\Auth\IssueTokenData;
use App\Enums\TokenPreset;
use App\Models\User;
use App\Services\Auth\ApiTokenIssuer;
use Laravel\Sanctum\NewAccessToken;

it('issues a sanctum token with abilities from the chosen preset', function () {
    $user = User::factory()->create();

    $token = app(ApiTokenIssuer::class)->issue(
        $user,
        new IssueTokenData(name: 'My Chrome', preset: TokenPreset::BrowserExtension),
    );

    expect($token)->toBeInstanceOf(NewAccessToken::class)
        ->and($token->accessToken->name)->toBe('My Chrome')
        ->and($token->accessToken->abilities)
        ->toBe(['bookmarks:create', 'categories:read'])
        ->and($token->plainTextToken)->toBeString();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'My Chrome',
    ]);
});

it('issues a wildcard token for the full-access preset', function () {
    $user = User::factory()->create();

    $token = app(ApiTokenIssuer::class)->issue(
        $user,
        new IssueTokenData(name: 'Backup script', preset: TokenPreset::FullAccess),
    );

    expect($token->accessToken->abilities)->toBe(['*']);
});
