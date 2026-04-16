<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('automatically renames duplicate slugs', function () {
    $user = User::factory()->create();
    $name = 'Test Category';
    $slug = Str::slug($name);

    Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Existing Category',
        'slug' => $slug,
    ]);

    $response = $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => $name,
        ]);

    $response->assertRedirect(route('categories.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => $name,
        'slug' => $slug.'-2',
    ]);
});

test('automatically renames multiple duplicate slugs', function () {
    $user = User::factory()->create();
    $name = 'Test Category';
    $slug = Str::slug($name);

    Category::factory()->create([
        'user_id' => $user->id,
        'slug' => $slug,
    ]);

    Category::factory()->create([
        'user_id' => $user->id,
        'slug' => $slug.'-2',
    ]);

    $response = $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => $name,
        ]);

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => $name,
        'slug' => $slug.'-3',
    ]);
});

test('updates category with duplicate slug name', function () {
    $user = User::factory()->create();

    $category1 = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'First Category',
        'slug' => 'first-category',
    ]);

    $category2 = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Second Category',
        'slug' => 'second-category',
    ]);

    $response = $this->actingAs($user)
        ->patch(route('categories.update', $category2), [
            'name' => 'First Category',
        ]);

    $response->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'id' => $category2->id,
        'name' => 'First Category',
        'slug' => 'first-category-2',
    ]);
});
