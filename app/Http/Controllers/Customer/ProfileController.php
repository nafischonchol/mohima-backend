<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdatePasswordRequest;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Services\Customer\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get authenticated customer profile details.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->profileService->getProfile($request->user());
    }

    /**
     * Update customer profile information.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->profileService->updateProfile($request->user(), $request->validated());
    }

    /**
     * Update customer password.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        return $this->profileService->updatePassword($request->user(), $request->validated());
    }
}
