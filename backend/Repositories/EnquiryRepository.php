<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class EnquiryRepository extends BaseRepository
{
    protected string $table = 'enquiries';

    public function findByReference(string $referenceNo): ?array
    {
        return $this->findWhere(['reference_no' => $referenceNo]);
    }

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM enquiries e INNER JOIN customers c ON c.id = e.customer_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT e.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
             FROM enquiries e
             INNER JOIN customers c ON c.id = e.customer_id' . $where . '
             ORDER BY e.created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function findDetailed(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT e.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
             FROM enquiries e
             INNER JOIN customers c ON c.id = e.customer_id
             WHERE e.id = ?',
            [$id]
        );
    }

    public function items(int $enquiryId): array
    {
        return Connection::fetchAll(
            'SELECT es.*, s.name AS service_name, pk.name AS package_name
             FROM enquiry_services es
             LEFT JOIN services s ON s.id = es.source_id AND es.source_type = "service"
             LEFT JOIN packages pk ON pk.id = es.source_id AND es.source_type = "package"
             WHERE es.enquiry_id = ?
             ORDER BY es.id ASC',
            [$enquiryId]
        );
    }

    public function replaceItems(int $enquiryId, array $items): void
    {
        Connection::execute('DELETE FROM enquiry_services WHERE enquiry_id = ?', [$enquiryId]);
        foreach ($items as $item) {
            $sourceType = in_array($item['source_type'] ?? 'service', ['service', 'package'], true)
                ? $item['source_type']
                : 'service';
            $sourceId = (int)($item['source_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            if ($sourceId <= 0) {
                continue;
            }
            Connection::execute(
                'INSERT INTO enquiry_services (enquiry_id, source_type, source_id, quantity, notes)
                 VALUES (?, ?, ?, ?, ?)',
                [$enquiryId, $sourceType, $sourceId, max(1, $quantity), $item['notes'] ?? null]
            );
        }
    }

    private function buildListWhere(string $search, string $status): array
    {
        $clauses = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $clauses[] = 'e.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(e.reference_no LIKE ? OR c.name LIKE ? OR c.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}