<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class ReviewRepository extends BaseRepository
{
    protected string $table = 'reviews';

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM reviews r
             INNER JOIN bookings b ON b.id = r.booking_id
             INNER JOIN customers c ON c.id = r.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT r.*, b.reference_no AS booking_no, c.name AS customer_name
             FROM reviews r
             INNER JOIN bookings b ON b.id = r.booking_id
             INNER JOIN customers c ON c.id = r.customer_id' . $where . '
             ORDER BY r.created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT r.*, b.reference_no AS booking_no, c.name AS customer_name, c.email AS customer_email,
                    s.name AS service_name
             FROM reviews r
             INNER JOIN bookings b ON b.id = r.booking_id
             INNER JOIN customers c ON c.id = r.customer_id
             LEFT JOIN services s ON s.id = r.service_id
             WHERE r.id = ?',
            [$id]
        );
    }

    public function approvedVisible(int $limit = 12): array
    {
        return Connection::fetchAll(
            'SELECT r.rating, r.title, r.comment, r.service_id, r.created_at,
                    c.name AS customer_name, s.name AS service_name
             FROM reviews r
             INNER JOIN customers c ON c.id = r.customer_id
             LEFT JOIN services s ON s.id = r.service_id
             WHERE r.status = "approved" AND r.is_visible = 1
             ORDER BY r.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function ratingSummary(): array
    {
        return Connection::fetchAll(
            'SELECT rating, COUNT(*) AS total FROM reviews WHERE status = "approved" GROUP BY rating ORDER BY rating DESC'
        );
    }

    public function forService(int $serviceId, int $limit = 10): array
    {
        return Connection::fetchAll(
            'SELECT r.rating, r.title, r.comment, r.created_at, c.name AS customer_name
             FROM reviews r
             INNER JOIN customers c ON c.id = r.customer_id
             WHERE r.service_id = ? AND r.status = "approved" AND r.is_visible = 1
             ORDER BY r.created_at DESC
             LIMIT ?',
            [$serviceId, $limit]
        );
    }

    public function averageGlobal(): float
    {
        return (float)Connection::fetchColumn(
            'SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE status = "approved"'
        );
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(c.name LIKE ? OR r.comment LIKE ? OR r.title LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}