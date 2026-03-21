<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'image' => 'required|string',
            'description' => 'required|string',
            'meaning' => 'required|string',
            'care' => 'required|string',
            'stock' => 'required|integer|min:0',
            'featured' => 'boolean',
            'holiday' => 'nullable|string',
        ];
    }
}
