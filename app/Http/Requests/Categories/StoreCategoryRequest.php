<?php

namespace App\Http\Requests\Categories;

use App\Data\Categories\CreateCategoryData;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }

    public function toData(): CreateCategoryData
    {
        return new CreateCategoryData(
            name: $this->string('name')->toString(),
            color: $this->filled('color') ? $this->string('color')->toString() : null,
        );
    }
}
