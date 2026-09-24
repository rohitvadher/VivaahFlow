<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class QuotationRepository extends BaseRepository
{
    protected string $table = 'quotations';

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM quotations q INNER JOIN customers c ON c.id = q.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT q.*, c.name AS customer_name, c.email AS customer_email,
                    (SELECT status FROM enquiries en WHERE en.id = q.enquiry_id) AS enquiry_status
             FROM quotations q
             INNER JOIN customers c ON c.id = q.customer_id' . $where . '
             ORDER BY q.created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT q.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
                    en.reference_no AS enquiry_no, en.status AS enquiry_status
             FROM quotations q
             INNER JOIN customers c ON c.id = q.customer_id
             LEFT JOIN enquiries en ON en.id = q.enquiry_id
             WHERE q.id = ?',
            [$id]
        );
    }

    public function items(int $quotationId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id ASC',
            [$quotationId]
        );
    }

    public function replaceItems(int $quotationId, array $items): void
    {
        Connection::execute('DELETE FROM quotation_items WHERE quotation_id = ?', [$quotationId]);
        foreach ($items as $item) {
            $sourceType = in_array($item['source_type'] ?? 'service', ['service', 'package'], true)
                ? $item['source_type']
                : 'service';
            Connection::execute(
                'INSERT INTO quotation_items
                 (quotation_id, source_type, source_id, item_name, quantity, unit_price, amount, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $quotationId,
                    $sourceType,
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

    public function forCustomer(int $customerId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM quotations WHERE customer_id = ? ORDER BY created_at DESC',
            [$customerId]
        );
    }

    public function pendingQuotations(int $limit = 6): array
    {
        return Connection::fetchAll(
            'SELECT q.*, c.name AS customer_name
             FROM quotations q
             INNER JOIN customers c ON c.id = q.customer_id
             WHERE q.status IN ("draft", "sent")
             ORDER BY q.updated_at ASC
             LIMIT ?',
            [$limit]
        );
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'q.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(q.reference_no LIKE ? OR c.name LIKE ? OR c.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}