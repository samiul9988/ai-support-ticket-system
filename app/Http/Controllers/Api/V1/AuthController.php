<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registration successful. Please check your email for verification.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 401, $e->errors());
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logged out successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $this->authService->profile($request->user());

        return $this->success(new UserResource($user));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $message = $this->authService->forgotPassword($request->input('email'));

            return $this->success(null, $message);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $message = $this->authService->resetPassword($request->validated());

            return $this->success(null, $message);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    public function verifyEmail(int $id, string $hash): JsonResponse
    {
        try {
            $this->authService->verifyEmail($id, $hash);

            return $this->success(null, 'Email verified successfully.');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        try {
            $message = $this->authService->resendVerificationEmail($request->user());

            return $this->success(null, $message);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
