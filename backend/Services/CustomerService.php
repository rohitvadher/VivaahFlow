<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculations\CalculationEngine;
use App\Core\BusinessException;
use App\Database\Connection;
use App\Helpers\Pagination;
use App\Helpers\Reference;
use App\Repositories\CustomerRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private ActivityService $activityService
    ) {
    }

    public function list(int $page, int $perPage, string $search, string $status): array
    {
        [$rows, $total] = $this->customerRepository->listPaginated($page, $perPage, $search, $status);
        $result = Pagination::build($rows, $total, $page, $perPage);
        foreach ($result['items'] as &$row) {
            $row['wedding_date_label'] = $row['wedding_date'] ?? null;
        }
        unset($row);
        return $result;
    }

    public function create(array $data, ?int $userId): array
    {
        $email = trim((string)($data['email'] ?? ''));
        if ($email !== '' && $this->customerRepository->findByEmail($email) !== null) {
            throw new BusinessException('A customer with this email already exists.', 409);
        }
        $customerId = Connection::transaction(function () use ($data, $email): int {
            $userLink = null;
            if ($email !== '' && !empty($data['create_login']) && !empty($data['password'])) {
                $userLink = $this->createCustomerLogin($data['name'], $email, $data['phone'] ?? null, $data['password']);
            }
            return $this->customerRepository->insert([
                'user_id' => $userLink,
                'name' => $data['name'],
                'email' => $email !== '' ? $email : null,
                'phone' => $data['phone'] ?? null,
                'wedding_date' => $data['wedding_date'] ?? null,
                'event_type' => $data['event_type'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'source' => $data['source'] ?? 'admin',
                'status' => $data['status'] ?? 'active',
            ]);
        });
        $this->activityService->log($userId, 'customer.created', 'customer', $customerId, 'Created customer ' . $data['name']);
        return $this->customerRepository->find($customerId);
    }

    private function createCustomerLogin(string $name, string $email, ?string $phone, string $password): int
    {
        $role = $this->roleRepository->findBySlug('customer');
        if ($role === null) {
            throw new BusinessException('Customer role is not configured.', 500);
        }
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new BusinessException('A login account with this email already exists.', 409);
        }
        return $this->userRepository->insert([
            'role_id' => $role['id'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active',
        ]);
    }

    public function find(int $id): array
    {
        $customer = $this->customerRepository->find($id);
        if ($customer === null) {
            throw new BusinessException('Customer not found.', 404);
        }
        $customer['history'] = [
            'bookings' => $this->customerRepository->bookingHistory($id),
            'quotations' => $this->customerRepository->quotationHistory($id),
            'payments' => $this->customerRepository->paymentHistory($id),
            'reviews' => $this->customerRepository->reviewHistory($id),
        ];
        foreach ($customer['history']['bookings'] as &$booking) {
            $summary = CalculationEngine::bookingPaymentSummary((int)$booking['id'], $booking);
            $booking['paid_amount'] = $summary['paid'];
            $booking['remaining_amount'] = $summary['remaining'];
            $booking['payment_status'] = $summary['payment_status'];
        }
        unset($booking);
        return $customer;
    }

    public function update(int $id, array $data, ?int $userId): array
    {
        $customer = $this->customerRepository->findOrFail($id);
        $email = trim((string)($data['email'] ?? $customer['email'] ?? ''));
        if ($email !== '') {
            $duplicate = $this->customerRepository->findByEmail($email);
            if ($duplicate !== null && (int)$duplicate['id'] !== $id) {
                throw new BusinessException('A customer with this email already exists.', 409);
            }
        }
        $this->customerRepository->update($id, [
            'name' => $data['name'] ?? $customer['name'],
            'email' => $email !== '' ? $email : null,
            'phone' => $data['phone'] ?? $customer['phone'],
            'wedding_date' => array_key_exists('wedding_date', $data) ? ($data['wedding_date'] ?: null) : $customer['wedding_date'],
            'event_type' => $data['event_type'] ?? $customer['event_type'],
            'address' => $data['address'] ?? $customer['address'],
            'city' => $data['city'] ?? $customer['city'],
            'source' => $data['source'] ?? $customer['source'],
            'status' => $data['status'] ?? $customer['status'],
        ]);
        $this->activityService->log($userId, 'customer.updated', 'customer', $id, 'Updated customer ' . $customer['name']);
        return $this->customerRepository->find($id);
    }

    public function changeStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessException('Invalid customer status.');
        }
        $customer = $this->customerRepository->findOrFail($id);
        $this->customerRepository->update($id, ['status' => $status]);
        if ($customer['user_id'] !== null) {
            $this->userRepository->update((int)$customer['user_id'], ['status' => $status]);
        }
        $this->activityService->log($userId, 'customer.status', 'customer', $id, 'Set customer status to ' . $status);
        return $this->customerRepository->find($id);
    }

    public function delete(int $id, ?int $userId): void
    {
        $customer = $this->customerRepository->findOrFail($id);
        $linked = (int)Connection::fetchColumn(
            'SELECT
                (SELECT COUNT(*) FROM bookings WHERE customer_id = ?) +
                (SELECT COUNT(*) FROM enquiries WHERE customer_id = ?) +
                (SELECT COUNT(*) FROM quotations WHERE customer_id = ?) +
                (SELECT COUNT(*) FROM payments WHERE customer_id = ?) +
                (SELECT COUNT(*) FROM reviews WHERE customer_id = ?) +
                (SELECT COUNT(*) FROM leads WHERE customer_id = ?)',
            [$id, $id, $id, $id, $id, $id]
        );
        if ($linked > 0) {
            throw new BusinessException('This customer has linked records and cannot be deleted. Deactivate it instead.', 409);
        }
        $this->customerRepository->delete($id);
        $this->activityService->log($userId, 'customer.deleted', 'customer', $id, 'Deleted customer ' . $customer['name']);
    }
}