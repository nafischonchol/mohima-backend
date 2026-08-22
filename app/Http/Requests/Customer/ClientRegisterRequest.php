<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ClientRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Core Account Credentials
            'contact_name'          => 'required|string|max:255',
            'username'              => 'required|string|min:5|max:50|unique:clients,username',
            'email'                 => 'required|email|max:255|unique:clients,email',
            'phone'                 => 'required|string|max:50|unique:clients,phone',
            'password'              => 'required|string|min:6',

            // Business Profile Information
            'company_name'          => 'required|string|max:255',
            'country'               => 'required|string|max:100',
            'website_or_fb'         => 'nullable|string|max:255',
            'trade_license'         => 'nullable|string|max:100',
            'company_address'       => 'required|string',
            'position'              => 'nullable|string|max:100',
            'business_type'         => 'required|string|in:retail,online,wholesale,other',
            'hear_about_us'         => 'required|string|in:search,social,referral,other',
            'interested_categories' => 'nullable|array',
            'interested_categories.*' => 'string',
            'business_introduction' => 'nullable|string',
            'nda_agreed'            => 'required|boolean|accepted',
        ];
    }
}
