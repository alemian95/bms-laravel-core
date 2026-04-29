<?php

use App\Enums\TokenPreset;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get(route('api-tokens.index'))->assertRedirect();
});

it('renders index with the user tokens list', function () {
    $user = User::factory()->create();
    $user->createToken('Existing', ['*']);

    $response = $this->actingAs($user)->get(route('api-tokens.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/api-tokens')
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Existing')
        );
});

it('does not show tokens belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $other->createToken('Foreign', ['*']);

    $response = $this->actingAs($user)->get(route('api-tokens.index'));

    $response->assertInertia(fn ($page) => $page->has('tokens', 0));
});

it('issues a token via store and flashes the plain text token once', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('api-tokens.store'), [
        'name' => 'My Chrome',
        'preset' => TokenPreset::BrowserExtension->value,
    ]);

    $response->assertRedirect(route('api-tokens.index'));
    $response->assertSessionHas('inertia.flash_data.newToken');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'My Chrome',
    ]);
});

it('rejects unknown preset', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('api-tokens.store'), [
        'name' => 'X',
        'preset' => 'super-admin',
    ]);

    $response->assertSessionHasErrors('preset');
});

it('revokes a token owned by the user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Tmp', ['*'])->accessToken;

    $response = $this->actingAs($user)->delete(route('api-tokens.destroy', $token->id));

    $response->assertRedirect(route('api-tokens.index'));
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('does not revoke a token belonging to another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreign = $other->createToken('Foreign', ['*'])->accessToken;

    $this->actingAs($user)->delete(route('api-tokens.destroy', $foreign->id));

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreign->id]);
});
