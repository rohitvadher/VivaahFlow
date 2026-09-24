<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class PackageRepository extends BaseRepository
{
    protected string $table = 'packages';

    public function findBySlug(string $slug): ?array
    {
        return $this->findWhere(['slug' => $slug]);
    }

    public function listPaginated(int $page, int $perPage, string $search, string $status): array
    {
        [$params, $where] = $this->buildListWhere($search, $status);
        $total = (int)Connection::fetchColumn('SELECT COUNT(*) FROM packages' . $where, $params);
        $rows = Connection::fetchAll(
            'SELECT * FROM packages' . $where . ' ORDER BY display_order ASC, id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    public function activePackages(): array
    {
        return Connection::fetchAll(
            'SELECT * FROM packages WHERE status = "active" ORDER BY is_featured DESC, display_order ASC, id DESC LIMIT 100'
        );
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
            $clauses[] = '(name LIKE ?)';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }

    public function services(int $packageId): array
    {
        return Connection::fetchAll(
            'SELECT ps.quantity, ps.id AS link_id, s.id AS service_id, s.name, s.slug, s.starting_price,
                    s.short_description, s.image, sc.name AS category_name
             FROM package_services ps
             INNER JOIN services s ON s.id = ps.service_id
             INNER JOIN service_categories sc ON sc.id = s.category_id
             WHERE ps.package_id = ?
             ORDER BY s.name ASC',
            [$packageId]
        );
    }

    public function syncServices(int $packageId, array $serviceIds): void
    {
        $this->deleteServices($packageId);
        foreach ($serviceIds as $index => $serviceId) {
            if (!is_numeric($serviceId)) {
                continue;
            }
            Connection::execute(
                'INSERT INTO package_services (package_id, service_id, quantity) VALUES (?, ?, 1)',
                [(int)$packageId, (int)$serviceId]
            );
        }
    }

    public function deleteServices(int $packageId): void
    {
        Connection::execute('DELETE FROM package_services WHERE package_id = ?', [$packageId]);
    }
}