<?php

namespace App\Http\Requests\Categories;

use App\Data\Categories\UpdateCategoryData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
        ];
    }

    public function toData(): UpdateCategoryData
    {
        return new UpdateCategoryData(
            name: $this->has('name') ? $this->string('name')->toString() : null,
            color: $this->has('color')
                ? ($this->filled('color') ? $this->string('color')->toString() : null)
                : null,
        );
    }
}
