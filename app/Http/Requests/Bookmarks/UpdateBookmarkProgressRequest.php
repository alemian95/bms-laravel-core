<?php

namespace App\Http\Requests\Bookmarks;

use App\Data\Bookmarks\UpdateBookmarkProgressData;
use App\Models\Bookmark;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookmarkProgressRequest extends FormRequest
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
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function toData(Bookmark $bookmark): UpdateBookmarkProgressData
    {
        return new UpdateBookmarkProgressData(
            bookmark: $bookmark,
            progress: $this->integer('progress'),
        );
    }
}
