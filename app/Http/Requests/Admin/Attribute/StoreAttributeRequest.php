<?php

namespace App\Http\Requests\Admin\Attribute;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends BaseFormRequest
{
    public function rules(): array
    {

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name'),
            ],
            'type' => ['required', 'string', 'in:text,rich_text,select,multi_select'],
            'values' => ['nullable', 'array'],
            'values.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'is_default_specification' => ['boolean'],
        ];
    }
}
