<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categories = Category::factory(5)->create([
            'user_id' => $user->id,
        ]);

        foreach ($categories as $category) {
            Bookmark::factory(3)->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
            ]);
        }

        Bookmark::factory(5)->create([
            'user_id' => $user->id,
            'category_id' => null,
        ]);
    }
}
