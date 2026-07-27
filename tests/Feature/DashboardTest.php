<?php

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Carbon;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('counters split bookmarks by reading progress', function () {
    $user = User::factory()->create();

    Bookmark::factory()->count(2)->for($user)->create(['reading_progress' => 0]);
    Bookmark::factory()->for($user)->create(['reading_progress' => 40]);
    Bookmark::factory()->count(3)->for($user)->create(['reading_progress' => 100]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('counters.total', 6)
            ->where('counters.unread', 2)
            ->where('counters.inProgress', 1)
            ->where('counters.completed', 3)
        );
});

test('dashboard figures never include another user\'s bookmarks', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    Bookmark::factory()->for($user)->create(['reading_progress' => 50]);
    Bookmark::factory()->count(4)->for($stranger)->create(['reading_progress' => 50]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('counters.total', 1)
            ->has('continueReading', 1)
        );
});

test('weekly saves cover twelve weeks and report empty weeks as zero', function () {
    $user = User::factory()->create();

    Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));

    Bookmark::factory()->count(2)->for($user)->create(['created_at' => Carbon::now()]);
    Bookmark::factory()->for($user)->create(['created_at' => Carbon::now()->subWeeks(3)]);
    Bookmark::factory()->for($user)->create(['created_at' => Carbon::now()->subWeeks(30)]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(function ($page) {
            $weekly = collect($page->toArray()['props']['weekly']);

            expect($weekly)->toHaveCount(12)
                ->and($weekly->sum('saved'))->toBe(3)
                ->and($weekly->last()['saved'])->toBe(2)
                ->and($weekly->where('saved', 0))->toHaveCount(10);
        });

    Carbon::setTestNow();
});

test('bookmarks without a category are grouped as uncategorized', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Engineering']);

    Bookmark::factory()->count(2)->for($user)->for($category)->create();
    Bookmark::factory()->for($user)->create(['category_id' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('byCategory.0', ['name' => 'Engineering', 'total' => 2])
            ->where('byCategory.1', ['name' => 'Uncategorized', 'total' => 1])
        );
});

test('domains past the top eight are folded into a single other bucket', function () {
    $user = User::factory()->create();

    foreach (range(1, 8) as $index) {
        Bookmark::factory()->count(2)->for($user)->create(['domain' => "top{$index}.com"]);
    }

    Bookmark::factory()->for($user)->create(['domain' => 'tail-a.com']);
    Bookmark::factory()->for($user)->create(['domain' => 'tail-b.com']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('topDomains', 9)
            ->where('topDomains.8', ['domain' => 'Other', 'total' => 2])
        );
});

test('continue reading lists only partially read bookmarks, most recent first', function () {
    $user = User::factory()->create();

    Bookmark::factory()->for($user)->create(['reading_progress' => 0]);
    Bookmark::factory()->for($user)->create(['reading_progress' => 100]);
    Bookmark::factory()->for($user)->create([
        'reading_progress' => 20,
        'updated_at' => now()->subDay(),
    ]);
    $newer = Bookmark::factory()->for($user)->create([
        'reading_progress' => 80,
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('continueReading', 2)
            ->where('continueReading.0.id', $newer->id)
            ->where('continueReading.0.progress', 80)
        );
});
