<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class CustomerRepository extends BaseRepository
{
    protected string $table = 'customers';

    public function findByEmail(string $email): ?array
    {
        $normalized = strtolower(trim($email));
        $row = $this->findWhere(['email' => $email]);
        if ($row !== null) {
            return $row;
        }
        // Case-insensitive fallback for rows stored before normalization.
        try {
            return Connection::fetchOne('SELECT * FROM customers WHERE LOWER(email) = ? LIMIT 1', [$normalized]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function findByUserId(?int $userId): ?array
    {
        if ($userId === null) {
            return null;
        }
        return $this->findWhere(['user_id' => $userId]);
    }

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn('SELECT COUNT(*) FROM customers' . $where, $params);
        $rows = Connection::fetchAll(
            'SELECT * FROM customers' . $where . ' ORDER BY created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }

    public function bookingHistory(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT b.*, q.reference_no AS quotation_no,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM bookings b
             LEFT JOIN quotations q ON q.id = b.quotation_id
             WHERE b.customer_id = ?
             ORDER BY b.created_at DESC',
            [$customerId]
        );
    }

    public function quotationHistory(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT q.* FROM quotations q WHERE q.customer_id = ? ORDER BY q.created_at DESC',
            [$customerId]
        );
    }

    public function paymentHistory(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT p.*, b.reference_no AS booking_no FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE p.customer_id = ? AND p.status = "recorded"
             ORDER BY p.payment_date DESC',
            [$customerId]
        );
    }

    public function reviewHistory(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT r.*, b.reference_no AS booking_no FROM reviews r
             INNER JOIN bookings b ON b.id = r.booking_id
             WHERE r.customer_id = ?
             ORDER BY r.created_at DESC',
            [$customerId]
        );
    }
}