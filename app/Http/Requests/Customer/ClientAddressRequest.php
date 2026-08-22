<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseFormRequest;

class ClientAddressRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:60',
            'phone' => 'required|string|max:14',
            'address' => 'required|string|max:250',
            'city' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'label' => 'nullable|string|max:50',
            'is_default' => 'nullable|boolean',
        ];
    }
}
