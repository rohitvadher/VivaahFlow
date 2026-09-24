<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class PaymentRepository extends BaseRepository
{
    protected string $table = 'payments';

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             INNER JOIN customers c ON c.id = b.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT p.*, b.reference_no AS booking_no, c.name AS customer_name
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             INNER JOIN customers c ON c.id = b.customer_id' . $where . '
             ORDER BY p.payment_date DESC, p.id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT p.*, b.reference_no AS booking_no, c.name AS customer_name, c.email AS customer_email
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE p.id = ?',
            [$id]
        );
    }

    public function totalRecorded(): int
    {
        return (int)Connection::fetchColumn(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "recorded"'
        );
    }

    public function pendingPaymentBookings(int $limit = 6): array
    {
        return Connection::fetchAll(
            'SELECT b.*, c.name AS customer_name,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE b.status <> "cancelled"
             HAVING paid < b.total_amount
             ORDER BY b.event_date ASC
             LIMIT ?',
            [$limit]
        );
    }

    public function monthlyRevenue(int $months): array
    {
        return Connection::fetchAll(
            'SELECT DATE_FORMAT(payment_date, "%Y-%m") AS period, COALESCE(SUM(amount), 0) AS revenue
             FROM payments
             WHERE status = "recorded"
               AND payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY period
             ORDER BY period ASC',
            [$months - 1]
        );
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(p.reference_no LIKE ? OR b.reference_no LIKE ? OR c.name LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}