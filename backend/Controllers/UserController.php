<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BusinessException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityService;
use App\Validators\Validator;

class UserController extends BaseController
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private ActivityService $activityService
    ) {
    }

    public function index(Request $request): void
    {
        $this->requirePermission('users.read');
        $roleId = $request->input('role_id');
        [$rows, $total] = $this->userRepository->listPaginated(
            $this->page(),
            $this->perPage(),
            $this->search(),
            $roleId !== null && $roleId !== '' ? (int)$roleId : null
        );
        foreach ($rows as &$row) {
            unset($row['password_hash']);
        }
        unset($row);
        $this->success(\App\Helpers\Pagination::build($rows, $total, $this->page(), $this->perPage()));
    }

    public function roles(Request $request): void
    {
        $this->requirePermission('roles.read');
        $this->success($this->roleRepository->allForSelect());
    }

    public function show(Request $request): void
    {
        $this->requirePermission('users.read');
        $user = $this->userRepository->findWithRole($this->routeId($request));
        if ($user === null) {
            throw new BusinessException('User not found.', 404);
        }
        unset($user['password_hash']);
        $this->success($user);
    }

    public function store(Request $request): void
    {
        $this->requirePermission('users.create');
        $result = Validator::validate($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|phone',
            'password' => 'required|string|min:6',
            'status' => 'nullable|in:active,inactive',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $data = $result['data'];
        $userId = $this->userRepository->insert([
            'role_id' => (int)$data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => password_hash((string)$data['password'], PASSWORD_DEFAULT),
            'status' => $data['status'] ?? 'active',
        ]);
        $this->activityService->log($this->userId(), 'user.created', 'user', $userId, 'Created user ' . $data['name']);
        $this->created($this->safeUser($userId), 'User created.');
    }

    public function update(Request $request): void
    {
        $this->requirePermission('users.update');
        $id = $this->routeId($request);
        $existing = $this->userRepository->findOrFail($id);
        $result = Validator::validate(array_merge($request->all(), ['ignore_id' => $id]), [
            'role_id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|phone',
            'password' => 'nullable|string|min:6',
            'status' => 'nullable|in:active,inactive',
        ]);
        if (!$result['valid']) {
            Response::validation($result['errors']);
        }
        $data = $result['data'];
        $update = [
            'role_id' => (int)$data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => $data['status'] ?? $existing['status'],
        ];
        if (!empty($data['password'])) {
            $update['password_hash'] = password_hash((string)$data['password'], PASSWORD_DEFAULT);
        }
        $this->userRepository->update($id, $update);
        $this->activityService->log($this->userId(), 'user.updated', 'user', $id, 'Updated user ' . $data['name']);
        $this->success($this->safeUser($id), 'User updated.');
    }

    public function status(Request $request): void
    {
        $this->requirePermission('users.update');
        $id = $this->routeId($request);
        $status = (string)$request->input('status', '');
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessException('Invalid user status.');
        }
        if ($id === $this->userId() && $status === 'inactive') {
            throw new BusinessException('You cannot deactivate your own account.');
        }
        $this->userRepository->findOrFail($id);
        $this->userRepository->update($id, ['status' => $status]);
        $this->activityService->log($this->userId(), 'user.status', 'user', $id, 'Set user status to ' . $status);
        $this->success($this->safeUser($id), 'User status updated.');
    }

    public function destroy(Request $request): void
    {
        $this->requirePermission('users.delete');
        $id = $this->routeId($request);
        if ($id === $this->userId()) {
            throw new BusinessException('You cannot delete your own account.');
        }
        $user = $this->userRepository->findOrFail($id);
        if (($user['role_slug'] ?? '') === 'admin') {
            $admins = (int)$this->userRepository->count('role_id = (SELECT id FROM roles WHERE slug = "admin") AND status = "active"');
            if ($admins <= 1) {
                throw new BusinessException('At least one active administrator must remain.');
            }
        }
        $this->userRepository->delete($id);
        $this->activityService->log($this->userId(), 'user.deleted', 'user', $id, 'Deleted user ' . $user['name']);
        $this->success(null, 'User deleted.');
    }

    private function safeUser(int $id): array
    {
        $user = $this->userRepository->findWithRole($id);
        if ($user === null) {
            return [];
        }
        unset($user['password_hash']);
        return $user;
    }
}