<?php

use App\Models\User;

test('guests get the locale inferred from the Accept-Language header', function () {
    $this->withHeader('Accept-Language', 'it-IT,it;q=0.9,en;q=0.8')
        ->get('/login')
        ->assertOk();

    expect(app()->getLocale())->toBe('it');
});

test('an unsupported Accept-Language header falls back to the default locale', function () {
    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get('/login')
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

test('the user preference wins over the browser header', function () {
    $user = User::factory()->create(['preferred_locale' => 'it']);

    $this->actingAs($user)
        ->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get(route('profile.edit'))
        ->assertOk();

    expect(app()->getLocale())->toBe('it');
});

test('the current locale is shared with every Inertia page', function () {
    $user = User::factory()->create(['preferred_locale' => 'it']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'it')
            ->where('supportedLocales', ['en', 'it'])
        );
});

test('an authenticated user can update the preferred locale', function () {
    $user = User::factory()->create(['preferred_locale' => 'en']);

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->patch(route('locale.update'), ['locale' => 'it'])
        ->assertRedirect(route('appearance.edit'));

    expect($user->refresh()->preferred_locale)->toBe('it');
});

test('an unsupported locale is rejected', function () {
    $user = User::factory()->create(['preferred_locale' => 'en']);

    $this->actingAs($user)
        ->patch(route('locale.update'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale');

    expect($user->refresh()->preferred_locale)->toBe('en');
});

test('guests cannot update the preferred locale', function () {
    $this->patch(route('locale.update'), ['locale' => 'it'])
        ->assertRedirect(route('login'));
});
