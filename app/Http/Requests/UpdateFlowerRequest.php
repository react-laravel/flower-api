<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string|max:255',
            'name_en' => 'string|max:255',
            'category' => 'string|max:255',
            'price' => 'numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'image' => 'string',
            'description' => 'string',
            'meaning' => 'string',
            'care' => 'string',
            'stock' => 'integer|min:0',
            'featured' => 'boolean',
            'holiday' => 'nullable|string',
        ];
    }
}
