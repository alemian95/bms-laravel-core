<?php

namespace App\Http\Requests\Bookmarks;

use App\Data\Bookmarks\CreateBookmarkData;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function toData(): CreateBookmarkData
    {
        return new CreateBookmarkData(
            url: $this->string('url')->toString(),
            categoryId: $this->integer('category_id') ?: null,
        );
    }
}
