<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class CategorySlugGenerator
{
    public function uniqueFor(User $user, string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name);
        $candidate = $base;
        $count = 2;

        while ($this->slugExists($user, $candidate, $exceptId)) {
            $candidate = "{$base}-{$count}";
            $count++;
        }

        return $candidate;
    }

    private function slugExists(User $user, string $slug, ?int $exceptId): bool
    {
        $query = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
