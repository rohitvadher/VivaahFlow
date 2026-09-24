<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class InvoiceRepository extends BaseRepository
{
    protected string $table = 'invoices';

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM invoices i
             INNER JOIN bookings b ON b.id = i.booking_id
             INNER JOIN customers c ON c.id = b.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT i.*, b.reference_no AS booking_no, c.name AS customer_name,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM invoices i
             INNER JOIN bookings b ON b.id = i.booking_id
             INNER JOIN customers c ON c.id = b.customer_id' . $where . '
             ORDER BY i.issue_date DESC, i.id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT i.*, b.reference_no AS booking_no, b.event_date, c.name AS customer_name, c.email AS customer_email,
                    c.phone AS customer_phone, c.address AS customer_address,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM invoices i
             INNER JOIN bookings b ON b.id = i.booking_id
             INNER JOIN customers c ON c.id = b.customer_id
             WHERE i.id = ?',
            [$id]
        );
    }

    public function forBooking(int $bookingId): ?array
    {
        return $this->findWhere(['booking_id' => $bookingId]);
    }

    public function forCustomer(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT i.*, b.reference_no AS booking_no, b.event_date,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM invoices i
             INNER JOIN bookings b ON b.id = i.booking_id
             WHERE b.customer_id = ? AND i.status NOT IN ("draft", "cancelled")
             ORDER BY i.issue_date DESC, i.id DESC',
            [$customerId]
        );
    }

    public function findForCustomer(int $id, int $customerId): ?array
    {
        return Connection::fetchOne(
            'SELECT i.*, b.reference_no AS booking_no, b.event_date,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id AND p.status = "recorded") AS paid
             FROM invoices i
             INNER JOIN bookings b ON b.id = i.booking_id
             WHERE i.id = ? AND b.customer_id = ?',
            [$id, $customerId]
        );
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'i.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(i.invoice_number LIKE ? OR b.reference_no LIKE ? OR c.name LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}