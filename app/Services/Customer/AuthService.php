<?php

namespace App\Services\Customer;

use App\Jobs\MergeWishlistJob;
use App\Models\Client;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Authenticate customer credentials and return response using response helper functions.
     */
    public function login(array $credentials, ?string $guestToken = null): JsonResponse
    {
        $login = $credentials['login'];
        $password = $credentials['password'];

        $client = Client::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $client || ! Hash::check($password, $client->password)) {
            return responseError('Invalid email/username or password.', 401);
        }

        // Check if approved by admin
        if ($client->status !== 'approved') {
            if ($client->status === 'pending') {
                return responseError('Your account is pending admin approval. Please wait for application review.', 403);
            }

            if ($client->status === 'rejected') {
                return responseError('Your account application was rejected by admin. Please contact support.', 403);
            }

            return responseError('Your account is not approved or inactive.', 403);
        }

        if (! $client->is_active) {
            return responseError('Your account is currently inactive.', 403);
        }

        $token = $client->createToken('customer_token')->plainTextToken;

        // Check if guest wishlist needs to be merged and dispatch job
        if ($guestToken && Wishlist::where('guest_token', $guestToken)->exists()) {
            MergeWishlistJob::dispatch($client->id, $guestToken);
        }

        return responseSuccess([
            'token' => $token,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'username' => $client->username,
                'phone' => $client->phone,
                'address' => $client->address,
                'status' => $client->status,
            ],
        ], 'Logged in successfully.');
    }

    /**
     * Register a new B2B client account using DB transaction and return response using helper.
     */
    public function register(array $validated): JsonResponse
    {
        $client = DB::transaction(function () use ($validated) {
            // 1. Create Core Client Record (Pending approval)
            $client = Client::create([
                'name' => $validated['contact_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'address' => $validated['company_address'],
                'status' => 'pending',
                'is_active' => false,
            ]);

            // 2. Create Client Detail Record
            $client->details()->create([
                'company_name' => $validated['company_name'],
                'country' => $validated['country'] ?? 'BD',
                'website_or_fb' => $validated['website_or_fb'] ?? null,
                'trade_license' => $validated['trade_license'] ?? null,
                'company_address' => $validated['company_address'],
                'contact_name' => $validated['contact_name'],
                'position' => $validated['position'],
                'business_type' => $validated['business_type'],
                'hear_about_us' => $validated['hear_about_us'],
                'interested_categories' => $validated['interested_categories'] ?? [],
                'business_introduction' => $validated['business_introduction'] ?? null,
                'nda_agreed' => $validated['nda_agreed'] ?? true,
            ]);

            return $client;
        });

        return responseSuccess([
            'id' => $client->id,
            'name' => $client->name,
            'username' => $client->username,
            'email' => $client->email,
            'status' => $client->status,
        ], 'Registration submitted successfully! Our team will review your application for B2B access.', 201);
    }
}
