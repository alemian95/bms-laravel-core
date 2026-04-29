<?php

namespace App\Http\Requests\Api\V1;

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
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function toFilters(): ListBookmarksFilters
    {
        return new ListBookmarksFilters(
            query: $this->filled('q') ? trim($this->string('q')->toString()) : null,
            categoryId: $this->filled('category_id') ? $this->integer('category_id') : null,
            page: max($this->integer('page', 1), 1),
            perPage: $this->filled('per_page') ? max(min($this->integer('per_page'), 50), 1) : 15,
            path: $this->url(),
            queryParams: $this->query(),
        );
    }
}
