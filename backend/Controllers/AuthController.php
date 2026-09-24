<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Permissions;
use App\Services\AuthService;
use App\Validators\Validator;

class AuthController extends BaseController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function csrf(Request $request): void
    {
        $this->success(['token' => Session::csrfToken()]);
    }

    public function me(Request $request): void
    {
        $user = $this->authService->user();
        $this->success(['user' => $user === null ? null : $this->shape($user)]);
    }

    public function login(Request $request): void
    {
        $result = Validator::validate($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        try {
            $attempt = $this->authService->attempt((string)$result['data']['email'], (string)$result['data']['password']);
        } catch (\Throwable $e) {
            error_log('[AUTH] login failed: ' . $e->getMessage());
            Response::error('Sign-in is temporarily unavailable. Please try again shortly.', 503);
        }
        if (!$attempt['ok']) {
            $status = match ($attempt['error'] ?? 'invalid') {
                'too_many' => 429,
                'inactive' => 403,
                'system' => 503,
                default => 401,
            };
            Response::error($attempt['message'], $status);
        }
        Response::success(['user' => $this->shape($attempt['user'])], 'Welcome back.');
    }

    public function register(Request $request): void
    {
        if (!$this->authService->isRegistrationOpen()) {
            Response::error('Customer registration is currently closed.', 403);
        }
        // NOTE: uniqueness is enforced authoritatively inside AuthService
        // (case-insensitive + race-safe). The validator uniqueness check is
        // intentionally omitted so a missing users table on fresh installs
        // does not produce a fatal 500 before setup completes.
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email',
            'phone' => 'nullable|phone',
            'password' => 'required|string|min:6',
            'wedding_date' => 'nullable|date',
            'event_type' => 'nullable|string|max:80',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        try {
            $attempt = $this->authService->registerCustomer($result['data']);
        } catch (\Throwable $e) {
            error_log('[AUTH] register failed: ' . $e->getMessage());
            Response::error('Registration is temporarily unavailable. Please try again shortly.', 503);
        }
        if (!$attempt['ok']) {
            $status = match ($attempt['error'] ?? 'system') {
                'closed' => 403,
                'exists' => 409,
                'validation' => 422,
                'system' => 503,
                default => 409,
            };
            if (($attempt['error'] ?? '') === 'exists') {
                Response::error($attempt['message'], $status, ['email' => [$attempt['message']]]);
            }
            Response::error($attempt['message'], $status);
        }
        Response::created(['user' => $this->shape($attempt['user'])], 'Your account has been created.');
    }

    public function logout(Request $request): void
    {
        $this->authService->logout();
        Response::success(null, 'You have been signed out.');
    }

    public function changePassword(Request $request): void
    {
        $user = $this->authService->user();
        if ($user === null) {
            Response::unauthorized();
        }
        $result = Validator::validate($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $outcome = $this->authService->changePassword($user, (string)$result['data']['current_password'], (string)$result['data']['new_password']);
        if (!$outcome['ok']) {
            Response::error($outcome['message'], 422);
        }
        Response::success(null, $outcome['message']);
    }

    public function updateProfile(Request $request): void
    {
        $user = $this->authService->user();
        if ($user === null) {
            Response::unauthorized();
        }
        $result = Validator::validate($request->all(), [
            'name' => 'required|string|min:3|max:120',
            'phone' => 'nullable|phone',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $this->authService->updateProfile($user, $result['data']);
        $fresh = $this->authService->user();
        Response::success(['user' => $fresh === null ? null : $this->shape($fresh)], 'Profile updated.');
    }

    private function shape(array $user): array
    {
        return [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role_slug'],
            'role_name' => $user['role_name'],
            'permissions' => Permissions::rolePermissions((string)$user['role_slug']),
            'customer_id' => isset($user['customer']['id']) ? (int)$user['customer']['id'] : null,
            'status' => $user['status'],
        ];
    }
}