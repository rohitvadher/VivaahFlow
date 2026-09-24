<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class CategoryRepository extends BaseRepository
{
    protected string $table = 'service_categories';

    public function activeCategories(): array
    {
        return Connection::fetchAll(
            'SELECT * FROM service_categories WHERE status = "active" ORDER BY display_order ASC, name ASC'
        );
    }

    public function listPaginated(int $page, int $perPage, string $search): array
    {
        [$params, $where] = $this->buildListWhere($search);
        $total = (int)Connection::fetchColumn('SELECT COUNT(*) FROM service_categories' . $where, $params);
        $rows = Connection::fetchAll(
            'SELECT * FROM service_categories' . $where . ' ORDER BY display_order ASC, id DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, ($page - 1) * $perPage])
        );
        return [$rows, $total];
    }

    private function buildListWhere(string $search): array
    {
        $params = [];
        $where = '';
        if ($search !== '') {
            $where = ' WHERE (name LIKE ? OR description LIKE ?)';
            $params = ['%' . $search . '%', '%' . $search . '%'];
        }
        return [$params, $where];
    }

    public function serviceCountByCategory(): array
    {
        return Connection::fetchAll(
            'SELECT sc.id, sc.name, COUNT(s.id) AS service_count
             FROM service_categories sc
             LEFT JOIN services s ON s.category_id = sc.id AND s.status = "active"
             GROUP BY sc.id, sc.name
             ORDER BY sc.display_order ASC'
        );
    }
}