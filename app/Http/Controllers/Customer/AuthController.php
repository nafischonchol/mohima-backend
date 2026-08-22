<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ClientLoginRequest;
use App\Http\Requests\Customer\ClientRegisterRequest;
use App\Services\Customer\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Customer login method.
     */
    public function login(ClientLoginRequest $request): JsonResponse
    {
        $guestToken = $request->header('X-Visitor-Id') ?: $request->input('guest_token');

        return $this->authService->login($request->validated(), $guestToken);
    }

    /**
     * Register a new B2B client account.
     */
    public function register(ClientRegisterRequest $request): JsonResponse
    {
        return $this->authService->register($request->validated());
    }
}
