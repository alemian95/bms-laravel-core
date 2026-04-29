<?php

use App\Models\User;

it('revokes the current access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Active', ['*']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/logout');

    $response->assertNoContent();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

it('rejects unauthenticated requests', function () {
    $this->postJson('/api/v1/logout')->assertUnauthorized();
});
