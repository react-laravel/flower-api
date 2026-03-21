<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => 'string|max:255',
            'slug' => [
                'string',
                'max:255',
                Rule::unique('categories')->ignore($categoryId),
            ],
            'icon' => 'string|max:10',
            'description' => 'string',
        ];
    }
}
