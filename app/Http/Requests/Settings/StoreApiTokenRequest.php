<?php

namespace App\Http\Requests\Settings;

use App\Data\Auth\IssueTokenData;
use App\Enums\TokenPreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64'],
            'preset' => ['required', 'string', Rule::enum(TokenPreset::class)],
        ];
    }

    public function toData(): IssueTokenData
    {
        return new IssueTokenData(
            name: $this->string('name')->toString(),
            preset: TokenPreset::from($this->string('preset')->toString()),
        );
    }
}
