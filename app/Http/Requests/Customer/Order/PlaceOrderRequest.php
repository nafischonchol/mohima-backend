<?php

namespace App\Http\Requests\Customer\Order;

use App\Http\Requests\BaseFormRequest;

class PlaceOrderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'address_id' => 'nullable|integer|exists:client_addresses,id',
            'name' => 'required_without:address_id|nullable|string|max:255',
            'phone' => 'required_without:address_id|nullable|string|max:50',
            'address' => 'required_without:address_id|nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'cart_ids' => 'required|array|min:1',
            'cart_ids.*' => 'required|integer|exists:carts,id',
            'payment_method' => 'nullable|string|in:cod,online',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_ids.required' => 'At least one cart item is required to place an order.',
            'cart_ids.*.exists' => 'Selected cart item is invalid.',
            'address_id.exists' => 'Selected delivery address is invalid.',
        ];
    }
}
