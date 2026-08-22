<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name'         => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($userId),
            ],
        ];
    }
}
