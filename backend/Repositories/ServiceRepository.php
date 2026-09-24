<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class ServiceRepository extends BaseRepository
{
    protected string $table = 'services';

    public function findBySlug(string $slug): ?array
    {
        return Connection::fetchOne(
            'SELECT s.*, sc.name AS category_name, sc.slug AS category_slug
             FROM services s
             INNER JOIN service_categories sc ON sc.id = s.category_id
             WHERE s.slug = ?',
            [$slug]
        );
    }

    public function findWithCategory(int $id): ?array
    {
        return Connection::fetchOne(
            'SELECT s.*, sc.name AS category_name, sc.slug AS category_slug
             FROM services s
             INNER JOIN service_categories sc ON sc.id = s.category_id
             WHERE s.id = ?',
            [$id]
        );
    }

    public function listPaginated(
        int $page,
        int $perPage,
        string $search,
        ?int $categoryId,
        string $status,
        bool $activeOnly = false
    ): array {
        [$params, $where] = $this->buildListWhere($search, $categoryId, $status, $activeOnly);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM services s INNER JOIN service_categories sc ON sc.id = s.category_id' . $where,
            $params
        );
        $rows = Connection::fetchAll(
            'SELECT s.*, sc.name AS category_name, sc.slug AS category_slug
             FROM services s
             INNER JOIN service_categories sc ON sc.id = s.category_id' . $where . '
             ORDER BY s.display_order ASC, s.id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function featuredActive(int $limit = 6): array
    {
        return Connection::fetchAll(
            'SELECT s.*, sc.name AS category_name, sc.slug AS category_slug
             FROM services s
             INNER JOIN service_categories sc ON sc.id = s.category_id
             WHERE s.status = "active"
             ORDER BY s.is_featured DESC, s.display_order ASC, s.id DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function allForPackage(): array
    {
        return Connection::fetchAll(
            'SELECT s.*, sc.name AS category_name
             FROM services s
             INNER JOIN service_categories sc ON sc.id = s.category_id
             WHERE s.status = "active"
             ORDER BY sc.display_order ASC, s.name ASC'
        );
    }

    public function byCategoryForPublic(int $limit = 100): array
    {
        return Connection::fetchAll(
            'SELECT s.id, s.category_id, s.name, s.slug, s.short_description, s.starting_price, s.image, s.duration_minutes,
                    sc.name AS category_name, sc.slug AS category_slug
             FROM services s
             INNER JOIN service_categories sc ON sc.id = s.category_id
             WHERE s.status = "active"
             ORDER BY sc.display_order ASC, s.display_order ASC, s.name ASC
             LIMIT ?',
            [$limit]
        );
    }

    public function idsUsedInBookings(): array
    {
        return Connection::fetchAll(
            "SELECT source_id, SUM(quantity) AS total_qty, COUNT(*) AS usage_count
             FROM booking_services
             WHERE source_type = 'service'
             GROUP BY source_id
             ORDER BY usage_count DESC"
        );
    }

    private function buildListWhere(string $search, ?int $categoryId, string $status, bool $activeOnly): array
    {
        $clauses = [];
        $params = [];
        if ($activeOnly) {
            $clauses[] = 's.status = "active"';
        } elseif ($status !== '' && $status !== 'all') {
            $clauses[] = 's.status = ?';
            $params[] = $status;
        }
        if ($categoryId !== null && $categoryId > 0) {
            $clauses[] = 's.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $clauses[] = '(s.name LIKE ? OR sc.name LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}