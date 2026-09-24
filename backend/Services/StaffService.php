<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\Pagination;
use App\Repositories\RoleRepository;
use App\Repositories\StaffRepository;
use App\Repositories\UserRepository;

class StaffService
{
    public function __construct(
        private StaffRepository $staffRepository,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->staffRepository->listPaginated($page, $perPage, $search, $status);
        return Pagination::build($rows, $total, $page, $perPage);
    }

    public function active(): array
    {
        return $this->staffRepository->activeStaff();
    }

    public function find(int $id): array
    {
        $staff = $this->staffRepository->find($id);
        if ($staff === null) {
            throw new BusinessException('Staff member not found.', 404);
        }
        $staff['assignments'] = $this->staffRepository->assignmentsForStaff($id);
        return $staff;
    }

    public function create(array $data, ?int $userId): array
    {
        $email = trim((string)($data['email'] ?? ''));
        $linkedUser = null;
        if (!empty($data['create_login']) && $email !== '' && !empty($data['password'])) {
            if ($this->userRepository->findByEmail($email) !== null) {
                throw new BusinessException('A login account with this email already exists.', 409);
            }
            $role = $this->roleRepository->findBySlug('staff');
            if ($role === null) {
                throw new BusinessException('Staff role is not configured.', 500);
            }
            $linkedUser = $this->userRepository->insert([
                'role_id' => $role['id'],
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'status' => 'active',
            ]);
        }
        $id = $this->staffRepository->insert([
            'user_id' => $linkedUser,
            'name' => trim($data['name']),
            'email' => $email !== '' ? $email : null,
            'phone' => $data['phone'] ?? null,
            'designation' => $data['designation'] ?? null,
            'specialty' => $data['specialty'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ]);
        $this->activityService->log($userId, 'staff.created', 'staff', $id, 'Created staff ' . $data['name']);
        return $this->find($id);
    }

    public function update(int $id, array $data, ?int $userId): array
    {
        $staff = $this->staffRepository->findOrFail($id);
        $this->staffRepository->update($id, [
            'name' => $data['name'] ?? $staff['name'],
            'email' => array_key_exists('email', $data) ? ($data['email'] ?: null) : $staff['email'],
            'phone' => $data['phone'] ?? $staff['phone'],
            'designation' => $data['designation'] ?? $staff['designation'],
            'specialty' => $data['specialty'] ?? $staff['specialty'],
            'status' => $data['status'] ?? $staff['status'],
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $staff['notes'],
        ]);
        if ($staff['user_id'] !== null && isset($data['status'])) {
            $this->userRepository->update((int)$staff['user_id'], ['status' => $data['status']]);
        }
        $this->activityService->log($userId, 'staff.updated', 'staff', $id, 'Updated staff ' . $staff['name']);
        return $this->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessException('Invalid staff status.');
        }
        $staff = $this->staffRepository->findOrFail($id);
        $this->staffRepository->update($id, ['status' => $status]);
        if ($staff['user_id'] !== null) {
            $this->userRepository->update((int)$staff['user_id'], ['status' => $status]);
        }
        $this->activityService->log($userId, 'staff.status', 'staff', $id, 'Set staff status to ' . $status);
        return $this->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $staff = $this->staffRepository->findOrFail($id);
        $assignments = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM staff_assignments WHERE staff_id = ? AND status <> "cancelled"',
            [$id]
        );
        if ($assignments > 0) {
            throw new BusinessException('This staff member has active assignments and cannot be deleted. Set them inactive instead.', 409);
        }
        $this->staffRepository->delete($id);
        $this->activityService->log($userId, 'staff.deleted', 'staff', $id, 'Deleted staff ' . $staff['name']);
    }
}