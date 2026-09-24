<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;

class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        return Connection::fetchOne(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE LOWER(u.email) = ?",
            [$email]
        );
    }

    public function findWithRole(int $id): ?array
    {
        return Connection::fetchOne(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?",
            [$id]
        );
    }

    public function listPaginated(int $page, int $perPage, string $search, ?int $roleId): array
    {
        [$searchParams, $where] = $this->buildListWhere($search, $roleId);
        $total = (int)Connection::fetchColumn(
            'SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id' . $where,
            $searchParams
        );
        $params = array_merge($searchParams, [$perPage, ($page - 1) * $perPage]);
        $rows = Connection::fetchAll(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u INNER JOIN roles r ON r.id = u.role_id' . $where . '
             ORDER BY u.created_at DESC LIMIT ? OFFSET ?',
            $params
        );
        return [$rows, $total];
    }

    private function buildListWhere(string $search, ?int $roleId): array
    {
        $clauses = [];
        $params = [];
        if ($roleId !== null && $roleId > 0) {
            $clauses[] = 'u.role_id = ?';
            $params[] = $roleId;
        }
        if ($search !== '') {
            $clauses[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$params, $where];
    }
}