<?php

namespace App\Http\Requests\Admin\Category;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
            ],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:1024'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_keyword' => ['nullable', 'array'],
            'meta_keyword.*' => ['string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
