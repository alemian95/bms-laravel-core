<?php

use App\Actions\Auth\RevokeApiToken;
use App\Data\Auth\RevokeTokenData;
use App\Models\User;

it('revokes a token by id for the owner', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Tmp', ['*'])->accessToken;

    app(RevokeApiToken::class)->handle(new RevokeTokenData($user, $token->id));

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('does not revoke tokens belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignToken = $other->createToken('Foreign', ['*'])->accessToken;

    app(RevokeApiToken::class)->handle(new RevokeTokenData($user, $foreignToken->id));

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreignToken->id]);
});
