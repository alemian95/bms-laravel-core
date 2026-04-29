<?php

namespace App\Http\Requests\Bookmarks;

use App\Data\Bookmarks\ListBookmarksFilters;
use Illuminate\Foundation\Http\FormRequest;

class IndexBookmarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toFilters(int $perPage = 9): ListBookmarksFilters
    {
        return new ListBookmarksFilters(
            query: $this->filled('q') ? trim($this->string('q')->toString()) : null,
            categorySlug: $this->filled('category') ? $this->string('category')->toString() : null,
            page: max($this->integer('page', 1), 1),
            perPage: $perPage,
            path: $this->url(),
            queryParams: $this->query(),
        );
    }
}
