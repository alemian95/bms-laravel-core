<?php

use App\Models\User;
use App\Services\Auth\ApiTokenRevoker;

it('revokes a token by id for the owner', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Tmp', ['*'])->accessToken;

    app(ApiTokenRevoker::class)->revoke($user, $token->id);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('does not revoke tokens belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignToken = $other->createToken('Foreign', ['*'])->accessToken;

    app(ApiTokenRevoker::class)->revoke($user, $foreignToken->id);

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreignToken->id]);
});
