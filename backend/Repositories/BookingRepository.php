<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class BookingRepository extends BaseRepository
{
    protected string $table = 'bookings';

    public function listPaginated(
        int $page,
        int $perPage,
        string $search,
        string $status,
        ?string $dateFrom,
        ?string $dateTo
    ): array {
        [$params, $where] = $this->buildListWhere($search, $status, $dateFrom, $dateTo);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM bookings b INNER JOIN customers c ON c.id = b.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid,
                    (SELECT COUNT(*) FROM events ev WHERE ev.booking_id = b.id) AS event_count
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id' . $where . '
             ORDER BY b.event_date ASC, b.created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                    c.wedding_date AS customer_wedding_date, q.reference_no AS quotation_no
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             LEFT JOIN quotations q ON q.id = b.quotation_id
             WHERE b.id = ?',
            [$id]
        );
    }

    public function services(int $bookingId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM booking_services WHERE booking_id = ? ORDER BY id ASC',
            [$bookingId]
        );
    }

    public function replaceServices(int $bookingId, array $services): void
    {
        Connection::execute('DELETE FROM booking_services WHERE booking_id = ?', [$bookingId]);
        foreach ($services as $item) {
            Connection::execute(
                'INSERT INTO booking_services
                 (booking_id, source_type, source_id, item_name, quantity, unit_price, amount, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $bookingId,
                    $item['source_type'] ?? 'service',
                    $item['source_id'] ?? null,
                    $item['item_name'] ?? '',
                    (int)($item['quantity'] ?? 1),
                    $item['unit_price'] ?? 0,
                    $item['amount'] ?? 0,
                    $item['notes'] ?? null,
                ]
            );
        }
    }

    public function upcoming(int $limit = 8): array
    {
        return Connection::fetchAll(
            'SELECT b.*, c.name AS customer_name
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE b.status IN ("confirmed", "scheduled", "in_progress")
               AND b.event_date >= ?
             ORDER BY b.event_date ASC
             LIMIT ?',
            [date('Y-m-d'), $limit]
        );
    }

    public function payments(int $bookingId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM payments WHERE booking_id = ? AND status = "recorded" ORDER BY payment_date DESC, id DESC',
            [$bookingId]
        );
    }

    public function forCustomer(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM bookings WHERE customer_id = ? ORDER BY created_at DESC',
            [$customerId]
        );
    }

    public function recent(int $limit = 8): array
    {
        return Connection::fetchAll(
            'SELECT b.*, c.name AS customer_name
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             ORDER BY b.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function expiredPendingPayments(): array
    {
        return Connection::fetchAll(
            'SELECT b.*, c.name AS customer_name
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE b.status <> "cancelled"
               AND b.event_date < ?',
            [date('Y-m-d')]
        );
    }

    private function buildListWhere(string $search, string $status, ?string $dateFrom, ?string $dateTo): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'b.status = ?';
            $params[] = $status;
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $clauses[] = 'b.event_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $clauses[] = 'b.event_date <= ?';
            $params[] = $dateTo;
        }
        if ($search !== '') {
            $clauses[] = '(b.reference_no LIKE ? OR c.name LIKE ? OR c.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}