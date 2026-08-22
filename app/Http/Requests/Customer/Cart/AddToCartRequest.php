<?php

namespace App\Http\Requests\Customer\Cart;

use App\Enums\ProductStatusEnum;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class AddToCartRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('status', ProductStatusEnum::ACTIVE->value),
            ],
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('is_active', true),
            ],
            'quantity' => 'required|integer|min:1',
        ];
    }
}
