<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Session;
use App\Database\Connection;
use App\Repositories\CustomerRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;

class AuthService
{
    private ?array $cachedUser = null;

    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private RoleRepository $roleRepository,
        private LoginAttemptRepository $loginAttemptRepository,
        private SettingsRepository $settingsRepository
    ) {
    }

    public function user(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }
        try {
            $user = $this->userRepository->findWithRole((int)$userId);
        } catch (\Throwable $e) {
            return null;
        }
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            $this->clearSession();
            return null;
        }
        try {
            $user['customer'] = $this->customerRepository->findByUserId((int)$user['id']);
        } catch (\Throwable $e) {
            $user['customer'] = null;
        }
        $this->cachedUser = $user;
        Request::current()->setUser($user);
        return $user;
    }

    private function clearSession(): void
    {
        Session::forget('user_id');
        Session::forget('user_role');
        Session::forget('user_name');
        Session::forget('user_email');
        $this->cachedUser = null;
    }

    public function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Session::set('user_role', (string)($user['role_slug'] ?? ''));
        Session::set('user_name', (string)($user['name'] ?? ''));
        Session::set('user_email', (string)($user['email'] ?? ''));
        try {
            $this->userRepository->update((int)$user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            // Login must succeed even when the audit column cannot be updated.
        }
        try {
            $user['customer'] = $this->customerRepository->findByUserId((int)$user['id']);
        } catch (\Throwable $e) {
            $user['customer'] = null;
        }
        $this->cachedUser = $user;
        Request::current()->setUser($user);
    }

    public function attempt(string $email, string $password): array
    {
        $maxAttempts = (int)app_config('security.login_max_attempts', 5);
        $lockMinutes = (int)app_config('security.login_lock_minutes', 15);
        $email = normalize_email($email);
        $ip = Request::current()->ip();

        try {
            $failures = $this->loginAttemptRepository->recentFailures($email, $ip, $lockMinutes);
        } catch (\Throwable $e) {
            $failures = 0;
        }
        if ($failures >= $maxAttempts) {
            return [
                'ok' => false,
                'error' => 'too_many',
                'message' => 'Too many failed attempts. Please try again after 15 minutes.',
            ];
        }

        try {
            $user = $this->userRepository->findByEmail($email);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'system', 'message' => 'Sign-in is temporarily unavailable. Please try again shortly.'];
        }
        if ($user === null || !password_verify($password, (string)($user['password_hash'] ?? ''))) {
            $this->recordAttempt($email, $ip, false);
            return ['ok' => false, 'error' => 'invalid', 'message' => 'Invalid email or password.'];
        }
        if (($user['status'] ?? '') !== 'active') {
            $this->recordAttempt($email, $ip, false);
            return ['ok' => false, 'error' => 'inactive', 'message' => 'This account has been deactivated.'];
        }

        $this->recordAttempt($email, $ip, true);
        // Refresh role join so role_slug/role_name are current.
        $fresh = $this->userRepository->findWithRole((int)$user['id']) ?? $user;
        $this->login($fresh);
        return ['ok' => true, 'user' => $fresh];
    }

    private function recordAttempt(string $email, string $ip, bool $success): void
    {
        try {
            $this->loginAttemptRepository->record($email, $ip, $success);
        } catch (\Throwable $e) {
            // Rate-limit persistence is best-effort on a fresh install.
        }
    }

    public function isRegistrationOpen(): bool
    {
        try {
            return $this->settingsRepository->isRegistrationOpen();
        } catch (\Throwable $e) {
            // Fresh installs without a settings table default to open.
            return true;
        }
    }

    public function registerCustomer(array $data): array
    {
        if (!$this->isRegistrationOpen()) {
            return ['ok' => false, 'error' => 'closed', 'message' => 'Customer registration is currently closed.'];
        }
        $email = normalize_email((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $name = trim((string)($data['name'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $weddingDate = $data['wedding_date'] ?? null;
        $eventType = isset($data['event_type']) ? trim((string)$data['event_type']) : null;

        if ($name === '' || $email === '' || $password === '') {
            return ['ok' => false, 'error' => 'validation', 'message' => 'Name, email and password are required.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'validation', 'message' => 'The email address is invalid.'];
        }
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'error' => 'validation', 'message' => 'The password must be at least 6 characters.'];
        }

        try {
            if ($this->userRepository->findByEmail($email) !== null) {
                return ['ok' => false, 'error' => 'exists', 'message' => 'An account with this email already exists. Try signing in instead.'];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'system', 'message' => 'Registration is temporarily unavailable. Please try again shortly.'];
        }

        $role = null;
        try {
            $role = $this->roleRepository->findBySlug('customer');
        } catch (\Throwable $e) {
            $role = null;
        }
        if ($role === null) {
            return ['ok' => false, 'error' => 'system', 'message' => 'Customer role is not configured. Please complete setup first.'];
        }

        try {
            $userId = Connection::transaction(function () use ($role, $name, $email, $password, $phone, $weddingDate, $eventType): int {
                $userId = $this->userRepository->insert([
                    'role_id' => $role['id'],
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'status' => 'active',
                ]);
                // A guest enquiry may already have created a customer row for
                // this email. Adopt it instead of failing on uq_customers_email.
                $existingCustomer = null;
                try {
                    $existingCustomer = $this->customerRepository->findByEmail($email);
                } catch (\Throwable $e) {
                    $existingCustomer = null;
                }
                if ($existingCustomer !== null) {
                    $linkedUser = $existingCustomer['user_id'] ?? null;
                    if ($linkedUser !== null && (int)$linkedUser !== $userId) {
                        throw new \App\Core\BusinessException('An account with this email already exists. Try signing in instead.', 409);
                    }
                    $this->customerRepository->update((int)$existingCustomer['id'], [
                        'user_id' => $userId,
                        'name' => $name,
                        'phone' => $phone !== '' ? $phone : $existingCustomer['phone'],
                        'wedding_date' => $weddingDate ?: $existingCustomer['wedding_date'],
                        'event_type' => ($eventType !== '' ? $eventType : null) ?? $existingCustomer['event_type'],
                        'source' => $existingCustomer['source'] ?? 'self_registration',
                        'status' => 'active',
                    ]);
                } else {
                    $this->customerRepository->insert([
                        'user_id' => $userId,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone !== '' ? $phone : null,
                        'wedding_date' => $weddingDate ?: null,
                        'event_type' => $eventType !== '' ? $eventType : null,
                        'address' => null,
                        'city' => null,
                        'source' => 'self_registration',
                        'status' => 'active',
                    ]);
                }
                return $userId;
            });
        } catch (\App\Core\BusinessException $e) {
            // Email genuinely taken (linked to another login): roll back the
            // just-created user so no orphan login remains.
            try {
                $orphan = $this->userRepository->findByEmail($email);
                if ($orphan !== null) {
                    $linked = $this->customerRepository->findByUserId((int)$orphan['id']);
                    if ($linked === null) {
                        Connection::execute('DELETE FROM users WHERE id = ?', [(int)$orphan['id']]);
                    }
                }
            } catch (\Throwable $cleanup) {
                // Best effort; the unique constraint keeps data safe.
            }
            return ['ok' => false, 'error' => 'exists', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'duplicate') || str_contains($message, 'uq_users_email') || str_contains($message, 'uq_customers_email')) {
                return ['ok' => false, 'error' => 'exists', 'message' => 'An account with this email already exists. Try signing in instead.'];
            }
            error_log('[AUTH] register failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'system', 'message' => 'Unable to create your account right now. Please try again.'];
        }

        $user = $this->userRepository->findWithRole($userId);
        if ($user === null) {
            return ['ok' => false, 'error' => 'system', 'message' => 'Account was created but could not be loaded. Please sign in.'];
        }
        $this->login($user);
        return ['ok' => true, 'user' => $user];
    }

    public function logout(): void
    {
        Session::destroy();
        $this->cachedUser = null;
    }

    public function changePassword(array $user, string $current, string $new): array
    {
        $stored = $this->userRepository->find((int)$user['id']);
        if ($stored === null || !password_verify($current, (string)($stored['password_hash'] ?? ''))) {
            return ['ok' => false, 'message' => 'Your current password is incorrect.'];
        }
        if (mb_strlen($new) < 6) {
            return ['ok' => false, 'message' => 'The new password must be at least 6 characters.'];
        }
        $this->userRepository->update((int)$user['id'], ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
        return ['ok' => true, 'message' => 'Password updated successfully.'];
    }

    public function updateProfile(array $user, array $data): void
    {
        $name = trim((string)($data['name'] ?? $user['name']));
        $phone = trim((string)($data['phone'] ?? ($user['phone'] ?? '')));
        $this->userRepository->update((int)$user['id'], [
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
        ]);
        Session::set('user_name', $name);
        $customer = $this->customerRepository->findByUserId((int)$user['id']);
        if ($customer !== null) {
            $this->customerRepository->update((int)$customer['id'], [
                'name' => $data['name'] ?? $customer['name'],
                'phone' => $data['phone'] ?? $customer['phone'],
                'wedding_date' => $data['wedding_date'] ?? $customer['wedding_date'],
                'event_type' => $data['event_type'] ?? $customer['event_type'],
                'address' => $data['address'] ?? $customer['address'],
                'city' => $data['city'] ?? $customer['city'],
            ]);
        }
    }
}
