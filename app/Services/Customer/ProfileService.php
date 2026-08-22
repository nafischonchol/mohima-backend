<?php

namespace App\Services\Customer;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function getProfile(Authenticatable $user)
    {
        try {
            $user->load('details');

            $data = [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'company_name' => $user->details->company_name ?? null,
                'status'       => $user->status,
                'is_active'    => $user->is_active,
            ];

            return responseSuccess($data, 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return responseError('Failed to retrieve profile: ' . $e->getMessage(), 500, $e);
        }
    }

    public function updateProfile(Authenticatable $user, array $data)
    {
        try {
            $user->update([
                'name'  => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            if (array_key_exists('company_name', $data)) {
                $user->details()->updateOrCreate(
                    ['client_id' => $user->id],
                    [
                        'company_name'    => $data['company_name'] ?? '',
                        'contact_name'    => $user->name,
                        'company_address' => $user->details->company_address ?? ($user->address ?? 'N/A'),
                    ]
                );
            }

            $user->load('details');

            $responseData = [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'company_name' => $user->details->company_name ?? null,
                'status'       => $user->status,
                'is_active'    => $user->is_active,
            ];

            return responseSuccess($responseData, 'Profile updated successfully');
        } catch (\Exception $e) {
            return responseError('Failed to update profile: ' . $e->getMessage(), 500, $e);
        }
    }

    public function updatePassword(Authenticatable $user, array $data)
    {
        try {
            if (! Hash::check($data['current_password'], $user->password)) {
                return responseError('The provided current password does not match our records.', 422);
            }

            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);

            return responseSuccess(null, 'Password updated successfully');
        } catch (\Exception $e) {
            return responseError('Failed to update password: ' . $e->getMessage(), 500, $e);
        }
    }
}
